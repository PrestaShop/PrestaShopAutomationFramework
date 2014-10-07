<?php

namespace PrestaShop\FunctionalTest;

class CartRulesManagementTest extends \PrestaShop\TestCase\LazyTestCase {
	
	public static function setupBeforeClass()
	{
		parent::setupBeforeClass();
		static::getShop()->getBackOfficeNavigator()->login();
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