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
	*	'country' => see below
	*	'behavior' => '!' (this tax only) or '+' (combine) or '*' (one after another),
	*	'description' => 'Description of the tax rule'
	* ]
	*
	* the country key above can have the following values:
	* 	- falsey value: all countries
	* 	- a single item, or an array of items, where an item is either:
	* 		- an integer (country_id)
	* 		- an array, having the following keys :
	* 			- id: integer country_id
	* 			- state: falsey value for all states, integer, or array of integer state_ids
	* 			- ziprange: a string representing a range of postcodes
	*
	*/
	public function createTaxRuleGroup($name, array $taxRules, $enabled = true)
	{
		$shop = $this->getShop();

		// $countries = $shop->getInformationRetriever()->getCountries();

		$browser = $shop->getBackOfficeNavigator()->visit('AdminTaxRulesGroup');

		$browser
		->click('#page-header-desc-tax_rules_group-new_tax_rules_group')
		->waitFor('#name')
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
			// We need to get the numerical tax rate to check correct display later
			$tax_rate = $browser->doThenComeBack(function() use ($taxRule){
				return $this->getTaxRateFromIdTax($taxRule['id_tax']);
			});

			$behavior = 0;
			if ($taxRule['behavior'] === '+')
				$behavior = 1;
			elseif ($taxRule['behavior'] === '*')
				$behavior = 2;

			
			$locations = [];
			if (empty($taxRule['country']))
			{
				$locations[] = ['country' => 0, 'state' => null, 'ziprange' => null];
			}
			else
			{
				$countries = [];

				// case: country => 1
				if (!is_array($taxRule['country'])) 
					$countries[] = $taxRule['country'];
				// case: country => [id => 1]
				elseif (is_array($taxRule['country']) && array_key_exists('id', $taxRule['country'])) 
					$countries[] = $taxRule['country'];
				// case: country => [[id => 1]]
				else
					$countries = $taxRule['country'];

				foreach ($countries as $country)
				{
					if (!is_array($country))
					{
						$country = $country ? $country : 0;
						$locations[] = ['country' => $country, 'state' => null, 'ziprange' => null];
					}
					else
					{
						$ziprange = isset($country['ziprange']) ? $country['ziprange'] : null;
						// [id => 1]
						if (!array_key_exists('state', $country))
						{
							$locations[] = ['country' => $country['id'], 'state' => null, 'ziprange' => $ziprange];
						}
						// [id => 1, state => [2, 3]]
						elseif (is_array($country['state']))
						{
							$locations[] = ['country' => $country['id'], 'state' => $country['state'], 'ziprange' => $ziprange];
						}
						// [id => 1, state => 2]
						elseif ($country['state'])
						{
							$locations[] = ['country' => $country['id'], 'state' => [$country['state']], 'ziprange' => $ziprange];
						}
						// [id => 1, state => null]
						else
						{
							$locations[] = ['country' => $country['id'], 'state' => [0], 'ziprange' => $ziprange];
						}
					}
				}
			}

			foreach ($locations as $location)
			{
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

				$browser->select('#country', $location['country']);

				if (isset($location['state']) && $location['state'])
				{
					$browser->waitFor('#states');
					$browser->multiSelect('#states', $location['state']);
					$browser->waitForUserInput();
				}

				if (isset($location['ziprange']) && $location['ziprange'])
				{
					$browser->fillIn('#zipcode', $location['ziprange']);
				}

				foreach ($country_names as $value => $name)
				{
					if ((int)$value === 0)
						continue;

					if (!$location['country'] || $location['country'] == $value)
					{
						$expected[] = [
							'country' => $name,
							'behavior' => $behavior_names[$behavior],
							'tax' => $tax_rate
						];
					}
				}

				$browser
				->select('#behavior', $behavior)
				->select('#id_tax', $taxRule['id_tax'])
				->clickButtonNamed('create_ruleAndStay')
				->ensureStandardSuccessMessageDisplayed('Could not add rule to TaxRuleGroup');
			}
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
				$out[$item['country']] .= '('.$item['country'].'='.$item['behavior'].':'.$item['tax'].')';
			}
			ksort($out);
			return implode("\n", $out);
		};

		$actual = $makeComparableResult($actual);
		$expected = $makeComparableResult($expected);

		// echo "Expected:\n$expected\n\nActual:\n$actual\n";

		if ($actual !== $expected)
		{
			/*$differ = new \SebastianBergmann\Diff\Differ();
			$diff = $differ->diff($expected, $actual);*/
			throw new \PrestaShop\Exception\TaxRuleGroupCreationIncorrectException();
		}
	}
}
