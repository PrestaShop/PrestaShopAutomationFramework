<?php

class TaxManagementTest extends \PrestaShop\TestCase\LazyTestCase
{
	public static function beforeAll()
	{
		static::getShop()->getBackOfficeNavigator()->login();
	}

	public function taxRules()
	{
		return [
			['OldFrenchVat', 19.6, true]
		];
	}

	/**
	* @dataProvider taxRules
	*/
	public function testTaxRuleCreation($name, $rate, $enabled)
	{
		$shop = static::getShop();
		$id_tax = $shop->getTaxManager()->createTaxRule($name, $rate, $enabled);
		$shop
		->getDataStore()
		->set('tax_rules.OldFrenchVat', ['id_tax' => $id_tax, 'rate' => $rate]);
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

	public function taxRuleGroups()
	{
		$groups = [];

		$groups[] = [
			'Same Single Rate For Everyone',
			[[
				'id_tax' => 'OldFrenchVat',
				'country' => null,
				'behavior' => '!'
			]],
			true
		];

		return $groups;
	}

	/**
	* @dataProvider taxRuleGroups
	*/
	public function testTaxRuleGroupCreation($name, array $taxRules, $enabled)
	{
		$shop = static::getShop();


		// Retrieve the id_tax's of the tax rules created earlier
		foreach ($taxRules as $n => $taxRule)
		{
			$tax_name = $taxRules[$n]['id_tax'];
			$taxRules[$n]['id_tax'] = $shop->getDataStore()->get("tax_rules.$tax_name")['id_tax'];
			// Sanity check, errors should have been caught earlier
			$this->assertInternalType('int', $taxRules[$n]['id_tax']);
			$this->assertGreaterThan(0, $taxRules[$n]['id_tax']);
		}

		$tm = $shop->getTaxManager();
		$tm->createTaxRuleGroup($name, $taxRules, $enabled);
	}
}
