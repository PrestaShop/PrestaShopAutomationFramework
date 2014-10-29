<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\TestCase;
use PrestaShop\PSTAF\Helper\FileSystem as FS;

class UpgradeTest extends TestCase
{
    protected static $cache_initial_state = false;

    protected static function shopManagerOptions()
    {
        return [
            'conf' => [
                'shop.filesystem_path' => __DIR__.'/UpgradeTest/prestashop',
                'shop.prestashop_version' => '1.6.0.9'
            ]
        ];
    }

    public function beforeUpgrade()
    {
        $scenario = $this->getJSONExample('invoice/simple-order.json');
        $output = InvoiceTest::runScenario($this->shop, $scenario);

        $this->writeArtefact('simple-order-before-upgrade.pdf', $output['pdf']);

        return $output;
    }

    public function afterUpgrade($before)
    {
        $this->shop->getBackOfficeNavigator()->login();

        $om = $this->shop->getOrderManager()->visit($before['id_order']);
        $this->writeArtefact('simple-order-after-upgrade.pdf', $om->getInvoicePDFData());
    }

    /**
     * @maxattempts 1
     */
    public function testUpgrade()
    {
        $before = $this->beforeUpgrade();

        // Install autoupgrade
        $this->shop
        ->getBackOfficeNavigator()
        // ->login() // already logged in from beforeUpgrade
        ->installModule('autoupgrade');

        // Copy files from the target version
        $targetFolder = FS::join(
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

        $this->afterUpgrade($before);
    }
}
