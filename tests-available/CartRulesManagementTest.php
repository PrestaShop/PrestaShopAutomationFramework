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
			'name' => 'Toto Rulez',
			'discount' => '10 after tax'
		));
	}

}