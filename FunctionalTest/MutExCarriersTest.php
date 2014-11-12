<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\LazyTestCase;

class MutExCarriersTest extends LazyTestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();
        static::getShop()->getBackOfficeNavigator()->login();
    }

    public function testOrderIsSplitIntoPackages()
    {
        // Remove default carriers
        $this->shop->getBackOfficeNavigator()
        ->deleteEntityById('AdminCarriers', 1)
        ->deleteEntityById('AdminCarriers', 2)
        ;

        $heavyProduct = $this->shop->getProductManager()->createProduct([
            'name' => 'Heavy Product',
            'price' => 10,
            'quantity' => 1,
            'weight' => 20
        ]);

        $bigProduct = $this->shop->getProductManager()->createProduct([
            'name' => 'Big Product',
            'price' => 10,
            'quantity' => 1,
            'height' => 20
        ]);


        $this->shop->getCarrierManager()->createCarrier([
            'name' => 'Light Products Only',
            'delay' => 'FAST',
            'handling' => false,
            'free' => true,
            'oorb' => 'disable',
            'max_weight' => 10
        ]);

        $this->shop->getCarrierManager()->createCarrier([
            'name' => 'Small Products Only',
            'delay' => 'SLOW',
            'handling' => false,
            'free' => true,
            'oorb' => 'disable',
            'max_height' => 10
        ]);

        $fops = $this->shop->getPageObject('FrontOfficeProductSheet');

        foreach ([$heavyProduct, $bigProduct] as $product) {
            $fops->visit($product['fo_url'])->addToCart();
        }

        $this->shop->getFrontOfficeNavigator()->login();

        $this->shop->getCheckoutManager()->orderCurrentCartFiveSteps([
            'stop_at' => 'carrier'
        ]);

        try {
            $err = $this->browser->find('div.alert.alert-danger', ['wait' => false]);
        } catch (\Exception $e) {
            // ignore exception, if we don't find an error, we're happy!            
        }

        if ($err) {
            throw new \PrestaShop\PSTAF\Exception\FailedTestException(
                "Error on the carrier selection page, but carriers should be available."
            );
        }
    }

}
