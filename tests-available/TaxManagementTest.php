<?php

class TaxManagementTest extends \PrestaShop\TestCase\TestCase
{
	public static function beforeAll()
	{
		static::getShop()->getBackOfficeNavigator()->login();
	}

	public function testAccessToAdminTaxes()
	{
		$shop = static::getShop();
		$shop->getBackOfficeNavigator()
		->visit('AdminTaxes')
		->ensureElementIsOnPage('#PS_TAX_on');
	}

	public function testTaxCreation()
	{
		static::getShop()->getTaxManager()->createTaxRule('Old French Vat', '19.6', true);
	}

	public function taxRuleGroupData()
	{
		$tm = static::getShop()->getTaxManager();

		$rates = [19.6, 5.5, 8];

		$tax_rules = [];

		foreach ($rates as $rate)
		{
			$tax_rules[$rate] = $tm->createTaxRule($rate.'% Tax Rule', $rate, true);
		}

		$specs = [];

		$specs[] = [
			'Same Single Rate For Everyone',
			['id_tax' => $tax_rules[19.6], 'country' => null, 'behaviour' => '!'],
			true
		];

		return $specs;
	}

	/**
	* @dataProvider taxRuleGroupData
	*/
	public function testTaxRuleGroupCreation($name, array $taxRules, $enabled)
	{

	}
}
