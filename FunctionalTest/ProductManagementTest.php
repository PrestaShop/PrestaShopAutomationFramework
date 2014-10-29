<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\LazyTestCase;

class ProductManagementTest extends LazyTestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();
        static::getShop()->getBackOfficeNavigator()->login();
    }

    public function testCreateProduct()
    {
        $shop = static::getShop();
        $data = $shop->getProductManager()->createProduct(array(
            'name' => 'Toto',
            'price' => 12.1234,
            'tax_rule' => 5,
            'quantity' => 100
        ));
    }

    public function testCreateProductWithSpecificPrice()
    {
        $shop = static::getShop();
        $browser = $shop->getBrowser();

        $product = $shop->getProductManager()->createProduct(array(
            'name' => 'Cheapy Product',
            'price' => 0.01,
            'quantity' => 2,
            'specific_price' => '80%'
        ));

        $shop
        ->getPageObject('FrontOfficeProductSheet')
        ->visit($product['fo_url'])
        ->setQuantity(1)
        ->addToCart();

        $summary = $shop->getPageObject('ShoppingCartSummary')->visit();

        $pct = $summary->getPercentReduction($product['id']);

        $summary->setQuantity(2, $product['id']);

        sleep(5);

        $pct2 = $summary->getPercentReduction($product['id']);

        if ($pct2 !== $pct) {
            throw new \PrestaShop\PSTAF\Exception\FailedTestException(
                "Reduction percent is wrong after adding a product in the Shopping Cart Summary. Changed from $pct to $pct2!"
            );
        }
    }

}
