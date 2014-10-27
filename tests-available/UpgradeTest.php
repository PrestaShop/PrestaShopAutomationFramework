<?php

namespace PrestaShop\FunctionalTest;

class UpgradeTest extends \PrestaShop\TestCase\TestCase
{
    protected static $cache_initial_state = false;

    protected static function shopManagerOptions()
    {
        return [
            'conf' => [
                'shop.filesystem_path' => __DIR__.'/UpgradeTest/prestashop'
            ]
        ];
    }

    /**
     * @maxattempts 1
     */
    public function testUpgrade()
    {
        // Install autoupgrade
        $this->shop
        ->getBackOfficeNavigator()
        ->login()
        ->installModule('autoupgrade');

        // Copy files from the target version
        $targetFolder = \PrestaShop\Helper\FileSystem::join(
            $this->shop->getBackOfficeFolderPath(),
            'autoupgrade',
            'latest',
            'prestashop'
        );

        $sourceFolder = static::getShopManager()
                        ->getConfiguration()
                        ->getAsAbsolutePath('shop.filesystem_path');

        // copy via a script executed by the browser to go around permissions issues
        $this->shop->getFileManager()->webCopyShopFiles($sourceFolder, $targetFolder);

        $this->shop->getBrowser()
        ->click('#currentConfiguration input[type="submit"]')
        ->click('input[value*=Expert]')
        ->select('[name="channel"]', 'directory')
        ->fillIn('[name="directory_num"]', '1.6.0.11')
        ->click('[name="submitConf-channel"]')
        ->acceptAlert()
        ->click('#upgradeNow')
        ->waitFor("{xpath}//h3[contains(., 'Upgrade Complete!')]", 120);
    }
}
