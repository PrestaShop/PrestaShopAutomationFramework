<?php

namespace PrestaShop\FunctionalTest;

class CartRulesManagementTest extends \PrestaShop\TestCase\LazyTestCase {
	
	public static function setupBeforeClass()
	{
		parent::setupBeforeClass();
		static::getShop()->getBackOfficeNavigator()->login();
	}

	public function testCreateCartRuleAppliedToProduct()
	{
		$shop = static::getShop();

		$product_name = 'My Cool Product';

		$shop->getProductManager()->createProduct(['name' => $product_name, 'price' => 42]);

		$data = $shop->getCartRulesManager()->createCartRule(array(
			'name' => "Discount After Tax On $product_name",
			'discount' => '10 after tax',
			'apply_to_product' => $product_name
		));
	}

	public function testCreateCartRule()
	{
		$shop = static::getShop();
		$data = $shop->getCartRulesManager()->createCartRule(array(
			'name' => 'Discount After Tax',
			'discount' => '10 after tax'
		));
	}

	public function testCreateCartRuleWithDiscount()
	{
		$shop = static::getShop();
		$data = $shop->getCartRulesManager()->createCartRule(array(
			'name' => 'Free Shipping!',
			'free_shipping' => true
		));
	}

}