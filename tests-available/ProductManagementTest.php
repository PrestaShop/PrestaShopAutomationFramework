<?php

namespace PrestaShop\FunctionalTest;

class ProductManagementTest extends \PrestaShop\TestCase\LazyTestCase {
	
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

}