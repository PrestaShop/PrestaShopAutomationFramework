<?php

namespace PrestaShop\ShopCapability;

class TaxManagement extends ShopCapability
{
    private $tax_rules_cache = [];
    private $tax_rules_groups_cache = [];

    /**
	 * Enable / Disable tax breakdown for composite taxes
	 * @param  boolean $on
	 * @return $this
	 */
    public function enableTaxBreakdownOnInvoices($on = true)
    {
        $browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminInvoices');
        $browser
        ->prestaShopSwitch('PS_INVOICE_TAXES_BREAKDOWN', $on)
        ->clickButtonNamed('submitOptionsinvoice')
        ->ensureStandardSuccessMessageDisplayed();

        if ($browser->prestaShopSwitchValue('PS_INVOICE_TAXES_BREAKDOWN') != $on)
            throw new \Exception(sprintf('Could not set tax to: %s', $on ? 'on' : 'off'));

        return $this;
    }

    /**
	 * Enable / Disable taxes
	 * @param  boolean $on
	 * @return $this
	 */
    public function enableTax($on = true)
    {
        $browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminTaxes');
        $browser
        ->prestaShopSwitch('PS_TAX', $on)
        ->clickButtonNamed('submitOptionstax')
        ->ensureStandardSuccessMessageDisplayed();

        if ($browser->prestaShopSwitchValue('PS_TAX') != $on)
            throw new \Exception(sprintf('Could not set tax to: %s', $on ? 'on' : 'off'));

        return $this;
    }

    /**
	 * Enable / Disable display of taxes in the shopping cart
	 * @param  boolean $on
	 * @return $this
	 */
    public function enableTaxInTheShoppingCart($on = true)
    {
        $browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminTaxes');
        $browser
        ->prestaShopSwitch('PS_TAX_DISPLAY', $on)
        ->clickButtonNamed('submitOptionstax')
        ->ensureStandardSuccessMessageDisplayed();

        if ($browser->prestaShopSwitchValue('PS_TAX_DISPLAY') != $on)
            throw new \Exception(sprintf('Could not set shopping cart tax to: %s', $on ? 'on' : 'off'));

        return $this;
    }

    /**
	 * Enable / Disable ecotax
	 * @param  boolean $on
	 * @return $this
	 */
    public function enableEcotax($on = true)
    {
        $browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminTaxes');
        $browser
        ->prestaShopSwitch('PS_USE_ECOTAX', $on)
        ->clickButtonNamed('submitOptionstax')
        ->ensureStandardSuccessMessageDisplayed();

        if ($browser->prestaShopSwitchValue('PS_USE_ECOTAX') != $on)
            throw new \Exception(sprintf('Could not set ecotax to: %s', $on ? 'on' : 'off'));

        if ($on && $this->shopVersionBefore('1.6.0.9'))
            $browser->reload();

        $browser->find('#PS_ECOTAX_TAX_RULES_GROUP_ID');

        return $this;
    }

    /**
	 * Set the tax group to be used for the ecotax
	 * @param int $id_tax_rules_group
	 */
    public function setEcotaxTaxGroup($id_tax_rules_group)
    {
        $browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminTaxes');
        $browser
        ->select('#PS_ECOTAX_TAX_RULES_GROUP_ID', $id_tax_rules_group)
        ->clickButtonNamed('submitOptionstax')
        ->ensureStandardSuccessMessageDisplayed();

        if ($browser->getValue('#PS_ECOTAX_TAX_RULES_GROUP_ID') != $id_tax_rules_group) {
            throw new \Exception('Could not set ecotax tax rules group.');
        }

        return $this;
    }

    /**
	 * Base tax on delivery or invoice address
	 * @param  string $address_type, either 'delivery address' or 'invoice address'
	 * @return $this
	 */
    public function baseTaxOn($address_type)
    {
        $browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminTaxes');

        $values = [
            'delivery address' => 'id_address_delivery',
            'invoice address' => 'id_address_invoice'
        ];

        if (!isset($values[$address_type]))
            throw new \Exception(sprintf('Unknown option %s', $address_type));

        $at = $values[$address_type];

        $browser
        ->select('#PS_TAX_ADDRESS_TYPE', $at)
        ->clickButtonNamed('submitOptionstax')
        ->ensureStandardSuccessMessageDisplayed();

        if ($browser->getValue('#PS_TAX_ADDRESS_TYPE') !== $at)
            throw new \Exception('Could not set correct address type for taxing.');

        return $this;
    }

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

        if ((int) $id_tax < 1)
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

        if ($actual_name !== $name || $actual_rate !== (float) $rate || $actual_enabled != $enabled)
            throw new \PrestaShop\Exception\TaxRuleCreationIncorrectException("stored results differ from submitted data");

        return (int) $id_tax;
    }

    /**
	 * Delete a tax rule
	 * @param  int $id_tax
	 * @return $this
	 */
    public function deleteTaxRule($id_tax)
    {
        $link = $this->getShop()->getBackOfficeNavigator()->getCRUDLink('AdminTaxes', 'delete', $id_tax);
        $this->getShop()->getBrowser()
        ->visit($link)
        ->ensureStandardSuccessMessageDisplayed();

        return $this;
    }

    /**
	 * Browse AdminTaxes to retrieve the tax rate corresponding to $id_tax
	 */
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
	 * 		- an array, having the following keys (only id is required):
	 * 			- id: integer country_id
	 * 			- state: falsey value for all states, integer, or array of integer state_ids
	 * 			- ziprange: a string representing a range of postcodes
	 *
	 */
    public function createTaxRulesGroup($name, array $taxRules, $enabled = true, $nocheck = false)
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
            throw new \PrestaShop\Exception\TaxRulesGroupCreationIncorrectException();

        $behavior_names = null;
        $country_names = null;
        $state_names = null;

        $expected = [];

        foreach ($taxRules as $taxRule) {
            // We need to get the numerical tax rate to check correct display later
            $tax_rate = $browser->doThenComeBack(function () use ($taxRule) {
                return $this->getTaxRateFromIdTax($taxRule['id_tax']);
            });

            $behavior = 0;
            if ($taxRule['behavior'] === '+')
                $behavior = 1;
            elseif ($taxRule['behavior'] === '*')
                $behavior = 2;

            $locations = [];
            if (empty($taxRule['country'])) {
                $locations[] = ['country' => 0, 'state' => null, 'ziprange' => null];
            } else {
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

                foreach ($countries as $country) {
                    if (!is_array($country)) {
                        $country = $country ? $country : 0;
                        $locations[] = ['country' => $country, 'state' => null, 'ziprange' => null];
                    } else {
                        $ziprange = isset($country['ziprange']) ? $country['ziprange'] : null;
                        // [id => 1]
                        if (!array_key_exists('state', $country)) {
                            $locations[] = ['country' => $country['id'], 'state' => null, 'ziprange' => $ziprange];
                        }
                        // [id => 1, state => [2, 3]]
                        elseif (is_array($country['state'])) {
                            $locations[] = ['country' => $country['id'], 'state' => $country['state'], 'ziprange' => $ziprange];
                        }
                        // [id => 1, state => 2]
                        elseif ($country['state']) {
                            $locations[] = ['country' => $country['id'], 'state' => [$country['state']], 'ziprange' => $ziprange];
                        }
                        // [id => 1, state => null]
                        else {
                            $locations[] = ['country' => $country['id'], 'state' => [0], 'ziprange' => $ziprange];
                        }
                    }
                }
            }

            foreach ($locations as $location) {
                $browser->click('#page-header-desc-tax_rule-new');
                $browser->waitFor('#id_tax');

                if (!$behavior_names) {
                    $behavior_names = $browser->getSelectOptions('#behavior');
                }

                if (!$country_names) {
                    $country_names = $browser->getSelectOptions('#country');
                }

                $browser->select('#country', $location['country']);

                $expected_states = [];
                if (isset($location['state']) && $location['state']) {
                    $browser->waitFor('#states');

                    if (!$state_names) {
                        $state_names = $browser->getSelectOptions('#states');
                    }

                    $browser->multiSelect('#states', $location['state']);

                    foreach ($location['state'] as $id)
                        $expected_states[] = $state_names[$id];

                    //$browser->waitForUserInput();
                } else {
                    $expected_states = ['--'];
                }

                if (isset($location['ziprange']) && $location['ziprange']) {
                    $browser->fillIn('#zipcode', $location['ziprange']);
                }

                foreach ($country_names as $value => $name) {
                    if ((int) $value === 0)
                        continue;

                    if (!$location['country'] || $location['country'] == $value) {
                        foreach ($expected_states as $state) {
                            $expected[] = [
                                'country' => $name,
                                'state' => $state,
                                'behavior' => $behavior_names[$behavior],
                                'tax' => $tax_rate,
                                'ziprange' => $location['ziprange']
                            ];
                        }
                    }
                }

                $browser
                ->select('#behavior', $behavior)
                ->select('#id_tax', $taxRule['id_tax'])
                ->clickButtonNamed('create_ruleAndStay')
                ->ensureStandardSuccessMessageDisplayed('Could not add rule to TaxRulesGroup');
            }
        }

        if (!$nocheck) {
            $paginator = $shop->getBackOfficePaginator()->getPaginatorFor('AdminTaxRulesGroup');

            $actual = [];

            foreach ($paginator->scrapeAll() as $row) {
                $actual[] = [
                    'country' => $row['country'],
                    'state' => $row['state'],
                    'behavior' => $row['behavior'],
                    'ziprange' =>  str_replace('--', '', $row['ziprange']),
                    'tax' => $row['tax'],
                ];
            }

            $makeComparableResult = function (array $list) {
                $out = [];
                foreach ($list as $item) {
                    $sort_key = $item['country'].':'.$item['state'];

                    if (!isset($out[$sort_key])) {
                        $out[$sort_key] = $item['country'].'['.$item['state'].'] =';
                    }

                    $zr = '';
                    if (isset($item['ziprange']) && $item['ziprange'])
                        $zr = preg_replace('/\s+/', '', $item['ziprange'].' => ');

                    $out[$sort_key] .= ' ('.$zr.$item['tax'].':'.$item['behavior'].')';
                }
                ksort($out);

                return implode("\n", $out);
            };

            $actual = $makeComparableResult($actual);
            $expected = $makeComparableResult($expected);

            if ($actual !== $expected) {
                /*$differ = new \SebastianBergmann\Diff\Differ();
				$diff = $differ->diff($expected, $actual);*/
                echo "Results differ!\n\nExpected:\n$expected\n\nActual:\n$actual\n";
                throw new \PrestaShop\Exception\TaxRulesGroupCreationIncorrectException();
            }
        }

        $id_tax_rules_group = $browser->getURLParameter('id_tax_rules_group');

        if ((int) $id_tax_rules_group < 1)
            throw new \PrestaShop\Exception\TaxRuleCreationIncorrectException("id_tax_rules_group not a positive integer");

        return $id_tax_rules_group;
    }

    public function getOrCreateTaxRule($name, $rate)
    {
        if ($name === null)
            $name = 'Tax Rule With '.$rate.'% Rate';

        if (!isset($this->tax_rules_cache[$name]))
            $this->tax_rules_cache[$name] = $this->createTaxRule($name, $rate);

        return $this->tax_rules_cache[$name];
    }

    public function getOrCreateTaxRulesGroupFromString($desc, $nocheck = false)
    {
        if (!isset($this->tax_rules_groups_cache[$desc])) {
            $parts = preg_split('/([+*!])/', $desc, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
            if (!preg_match('/^[+*!]$/', $parts[0]))
                $parts = array_merge([isset($parts[1]) ? $parts[1] : '!'], $parts);

            $tax_rules = [];

            for ($i = 0; $i < count($parts) - 1; $i += 2) {
                $tax_rules[] = [
                    'country' => null,
                    'behavior' => $parts[$i],
                    'description' => $parts[$i + 1].'% all countries',
                    'id_tax' => $this->getOrCreateTaxRule(null, trim($parts[$i + 1]))
                ];
            }

            $this->tax_rules_groups_cache[$desc] = $this->createTaxRulesGroup("$desc TRG", $tax_rules, true, $nocheck);
        }

        return $this->tax_rules_groups_cache[$desc];
    }

    /**
	 * Delete a tax rules group
	 * @param  int $id_tax_rules_group
	 * @return $this
	 */
    public function deleteTaxRulesGroup($id_tax_rules_group)
    {
        $link = $this->getShop()->getBackOfficeNavigator()->getCRUDLink('AdminTaxRulesGroup', 'delete', $id_tax_rules_group);
        $this->getShop()->getBrowser()
        ->visit($link)
        ->ensureStandardSuccessMessageDisplayed();

        return $this;
    }
}
