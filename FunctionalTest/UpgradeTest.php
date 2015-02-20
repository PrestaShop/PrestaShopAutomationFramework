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
                'shop.prestashop_version' => '1.6.0.11'
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

        $this->shop->getPreferencesManager()->setMaintenanceMode(false);

        $this->browser->clearCookies();

        $scenario = $this->getJSONExample('invoice/simple-order.json');
        // Rename the carrier so that this one gets picked
        $scenario['carrier']['name'] .= ' Two';
        $output = InvoiceTest::runScenario($this->shop, $scenario);

        $this->shop->getTaxManager()->getOrCreateTaxRulesGroupFromString("3.5 + 4.1 + 7");
    }

    /**
     * @maxattempts 1
     */
    public function testUpgrade()
    {
        $before = $this->beforeUpgrade();

        $sourceFolder = static::getShopManager()
                        ->getNewConfiguration()
                        ->getAsAbsolutePath('shop.filesystem_path');

        // If we put an autoupgrade module in the source of the target version, use this one.
        $autoupgradeSourceFolder = FS::join($sourceFolder, 'modules', 'autoupgrade');
        if (FS::exists($autoupgradeSourceFolder)) {
            $autoupgradeTargetFolder = FS::join($this->shop->getFilesystemPath(), 'modules', 'autoupgrade');
            $this->shop->getFileManager()->webCopyShopFiles($autoupgradeSourceFolder,$autoupgradeTargetFolder);
        }

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

        // copy via a script executed by the browser to go around permissions issues
        $this->shop->getFileManager()->webCopyShopFiles($sourceFolder, $targetFolder);

        $this->shop->getBrowser()
        ->click('#currentConfiguration input[type="submit"]')
        ->click('input[value*=Expert]')
        ->select('[name="channel"]', 'directory')
        ->fillIn('[name="directory_num"]', '1.6.0.12')
        ->click('[name="submitConf-channel"]')
        ->acceptAlert()
        ->click('#upgradeNow')
        ->waitFor("{xpath}//h3[contains(., 'Upgrade Complete!')]", 120);

        $this->afterUpgrade($before);
    }
}
