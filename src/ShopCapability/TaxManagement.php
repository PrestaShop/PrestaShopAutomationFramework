<?php

namespace PrestaShop\ShopCapability;

class TaxManagement extends ShopCapability
{
	/**
	* Create a Tax Rule.
	*
	* Assumes:
	* - successfuly logged in to the back-office
	* - on a back-office page
	*
	* @return the id of the created tax rule
	*/
	public function createTaxRule($name, $rate, $enabled = true)
	{
		$shop = $this->getShop();

		$browser = $shop->getBackOfficeNavigator()->visit('AdminTaxes');

		$browser
		->click('#page-header-desc-tax-new_tax')
		->fillIn($this->i18nFieldName('#name'), $name)
		->fillIn('#rate', $rate)
		->prestaShopSwitch('active', $enabled)
		->click('button[name=submitAddtax]')
		->ensureStandardSuccessMessageDisplayed();

		$id_tax = $browser->getURLParameter('id_tax');

		if ((int)$id_tax < 1)
			throw new \PrestaShop\Exception\TaxRuleCreationIncorrectException("id_tax not a positive integer");

		$check_url = \PrestaShop\Helper\URL::filterParameters(
			$browser->getCurrentURL(),
			['controller', 'id_tax', 'token'],
			['updatetax' => 1]
		);

		$browser->visit($check_url);

		$actual_name = $browser->getValue($this->i18nFieldName('#name'));
		$actual_rate = $this->i18nParse($browser->getValue('#rate'), 'float');
		$actual_enabled = $browser->prestaShopSwitchValue('active');

		if ($actual_name !== $name || $actual_rate !== (float)$rate || $actual_enabled != $enabled)
			throw new \PrestaShop\Exception\TaxRuleCreationIncorrectException("stored results differ from submitted data");

		return (int)$id_tax;
	}

	/**
	* Create a Tax Rule Group
	*
	* $taxRules is an array of arrays describing the Tax Rules composing the group
	* each element has the following structure:
	* [
	*	'id_tax' => some_positive_integer - anything else treated as no tax,
	*	'country' => null or array of integer country_id's,
	*	'behavior' => '!' (this tax only) or '+' (combine) or '*' (one after another),
	*	'description' => 'Description of the tax rule'
	* ]
	*
	*/
	public function createTaxRuleGroup($name, array $taxRules, $enabled = true)
	{
		$shop = $this->getShop();

		$browser = $shop->getBackOfficeNavigator()->visit('AdminTaxRulesGroup');

		$browser
		->click('#page-header-desc-tax_rules_group-new_tax_rules_group')
		->fillIn('#name', $name)
		->prestaShopSwitch('active', $enabled)
		->click('button[name=submitAddtax_rules_groupAndStay]')
		->ensureStandardSuccessMessageDisplayed();

		$actual_name = $browser->getValue('#name');
		$actual_enabled = $browser->prestaShopSwitchValue('active');

		if ($actual_name !== $name || $actual_enabled !== $enabled)
			throw new \PrestaShop\Exception\TaxRuleGroupCreationIncorrectException();

		foreach ($taxRules as $taxRule)
		{
			$browser->click('#page-header-desc-tax_rule-new');

			if (!empty($taxRule['country']))
			{
				$browser->select('#country', $taxRule['country']);
			}

			$behavior = 0;
			if ($taxRule['behavior'] === '+')
				$behavior = 1;
			elseif ($taxRule['behavior'] === '*')
				$behavior = 2;

			$behavior_name = null;

			$browser
			->waitFor('#id_tax')
			->select('#behavior', $behavior, $behavior_name)
			->select('#id_tax', $taxRule['id_tax'])
			->clickButtonNamed('create_ruleAndStay')
			->ensureStandardSuccessMessageDisplayed();

			$paginator = $shop->getBackOfficePaginator()->getPaginatorFor('AdminTaxRulesGroup');

			echo "behavior: $behavior_name\n";

			//print_r($paginator->scrapeAll());

			$browser->waitForUserInput();
		}
	}
}
