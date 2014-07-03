<?php

class TaxManagementTest extends \PrestaShop\TestCase\LazyTestCase
{
	public static function beforeAll()
	{
		static::getShop()->getBackOfficeNavigator()->login();
	}
	/*
	public function testAccessToAdminTaxes()
	{
		$shop = static::getShop();
		$shop->getBackOfficeNavigator()
		->visit('AdminTaxes')
		->ensureElementIsOnPage('#PS_TAX_on');
	}*/

	public function testTaxCreation()
	{
		static::getShop()->getTaxManager()->createTaxRule('toto', '19.6', true);
	}
}
