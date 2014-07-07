<?php

class TaxManagementTest extends \PrestaShop\TestCase\TestCase
{
	public static function beforeAll()
	{
		$shop = static::getShop();
		$ds   = $shop->getDataStore();
		$tm   = $shop->getTaxManager();

		$shop->getBackOfficeNavigator()->login();

		$ds->set("tax_rules.a", ['id_tax' => $tm->createTaxRule('Old French Vat', 19.6, true)]);
		/*$ds->set("tax_rules.b", ['id_tax' => $tm->createTaxRule('Some Medium Tax Rate', 8.5, true)]);
		$ds->set("tax_rules.c", ['id_tax' => $tm->createTaxRule('A Tiny Tax', 5, true)]);*/
	}

	/*
	public function testAccessToAdminTaxes()
	{
		$shop = static::getShop();
		$shop->getBackOfficeNavigator()
		->visit('AdminTaxes')
		->ensureElementIsOnPage('#PS_TAX_on');
	}

	public function testTaxCreation()
	{
		static::getShop()
		->getTaxManager()
		->createTaxRule('Old French Vat', '19.6', true);
	}*/

	public function taxRuleGroupData()
	{
		$shop = static::getShop();
		$ds = $shop->getDataStore();

		print_r($ds->toArray());

		$specs[] = [
			'Same Single Rate For Everyone',
			[[
				'id_tax' => $ds->get('tax_rules.a.id_tax'),
				'country' => null,
				'behavior' => '!'
			]],
			true
		];

		return $specs;
	}

	/**
	* @dataProvider taxRuleGroupData
	*/
	public function testTaxRuleGroupCreation($name, array $taxRules, $enabled)
	{
		$shop = static::getShop();
		$tm = $shop->getTaxManager();
		$tm->createTaxRuleGroup($name, $taxRules, $enabled);
	}
}
