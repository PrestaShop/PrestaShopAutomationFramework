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

	public function getTaxRateFromIdTax($id_tax)
	{
		$url = $this
			->getShop()
			->getBackOfficeNavigator()
			->visit('AdminTaxes')
			->getCurrentURL();

		$url .= '&id_tax='.$id_tax.'&updatetax';

		$rate = $this->getBrowser()->visit($url)->getValue('#rate');

		return $this->i18nParse($rate, 'float');
	}

	/**
	* Create a Tax Rule Group
	* presupposes: logged in to the back office and on a back office page
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

		// $countries = $shop->getInformationRetriever()->getCountries();

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

		$behavior_names = null;
		$country_names = null;

		$expected = [];

		foreach ($taxRules as $taxRule)
		{
			$tax_rate = $browser->doThenComeBack(function() use ($taxRule){
				return $this->getTaxRateFromIdTax($taxRule['id_tax']);
			});

			$browser->click('#page-header-desc-tax_rule-new');
			$browser->waitFor('#id_tax');

			if (!$behavior_names)
			{
				$behavior_names = $browser->getSelectOptions('#behavior');
			}

			if (!$country_names)
			{
				$country_names = $browser->getSelectOptions('#country');
			}

			$behavior = 0;
			if ($taxRule['behavior'] === '+')
				$behavior = 1;
			elseif ($taxRule['behavior'] === '*')
				$behavior = 2;

			if (!empty($taxRule['country']) && (int)$taxRule['country'] > 0)
			{
				$browser->select('#country', $taxRule['country']);

				$expected[] = [
					'country' => $country_names[$taxRule['country']],
					'behavior' => $behavior_names[$behavior],
					'tax' => $tax_rate
				];
			}
			else
			{
				foreach ($country_names as $value => $name)
				{
					if ($value > 0)
					{
						$expected[] = [
							'country' => $name,
							'behavior' => $behavior_names[$behavior],
							'tax' => $tax_rate
						];
					}
				}
			}

			$browser
			->select('#behavior', $behavior)
			->select('#id_tax', $taxRule['id_tax'])
			->clickButtonNamed('create_ruleAndStay')
			->ensureStandardSuccessMessageDisplayed();
		}

		$paginator = $shop->getBackOfficePaginator()->getPaginatorFor('AdminTaxRulesGroup');

		$actual = [];

		foreach ($paginator->scrapeAll() as $row)
		{
			$actual[] = [
				'country' => $row['country'],
				'behavior' => $row['behavior'],
				'tax' => $row['tax']
			];
		}

		$makeComparableResult = function(array $list)
		{
			$out = [];
			foreach ($list as $item)
			{
				if (!isset($out[$item['country']]))
				{
					$out[$item['country']] = '';
				}
				$out[$item['country']] .= '('.$item['behavior'].':'.$item['tax'].')';
			}
			ksort($out);
			return $out;
		};

		$actual = $makeComparableResult($actual);
		$expected = $makeComparableResult($expected);

		if ($actual !== $expected)
		{
			throw new \PrestaShop\Exception\TaxRuleGroupCreationIncorrectException();
		}
	}
}
