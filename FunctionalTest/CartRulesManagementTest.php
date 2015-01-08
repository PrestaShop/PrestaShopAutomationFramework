<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\LazyTestCase;

class CartRulesManagementTest extends LazyTestCase
{
    public static function setupBeforeClass()
    {
        parent::setupBeforeClass();
        static::getShop()->getBackOfficeNavigator()->login();
    }

    public function testCreateCartRuleAppliedToProduct()
    {
        $shop = $this->shop;

        $product_name = 'My Cool Product';

        $product = $shop->getProductManager()->createProduct(['name' => $product_name, 'price' => 42]);

        $shop->getCartRulesManager()->createCartRule(array(
            'name' => "Discount After Tax On $product_name",
            'discount' => '10 after tax',
            'apply_to_product' => $product['id']
        ));
    }


    public function testCreateCartRule()
    {
        $this->shop->getCartRulesManager()->createCartRule(array(
            'name' => 'Discount After Tax',
            'discount' => '10 after tax'
        ));
    }

    public function testCreateCartRuleWithDiscount()
    {
        $this->shop->getCartRulesManager()->createCartRule(array(
            'name' => 'Free Shipping!',
            'free_shipping' => true
        ));
    }

}
