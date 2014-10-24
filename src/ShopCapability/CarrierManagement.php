<?php

namespace PrestaShop\ShopCapability;

class CarrierManagement extends ShopCapability
{
    /**
	 * Create a carrier
	 *
	 * options may contain:
	 * - name
	 * - delay
	 * - free: truthy for free shipping, falsey for no
	 * - handling: boolishean, apply handling costs
	 * - tax_rule: id of the tax rule group to apply
	 * - based_on: string, 'price' => base on price, anything else => 'weight'
	 * - ranges: array with upper limits as keys and prices/weights as values
	 * - oorb: out of range behaviour, 'highest' or 'disable'
	 */
    public function createCarrier($options)
    {
        $browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminCarriers')
        ->click('#page-header-desc-carrier-new_carrier')
        ->click('a[data-selenium-id=create_custom_carrier]')
        ->waitFor('#name')
        ->fillIn('#name', $options['name'])
        ->fillIn($this->i18nFieldName('#delay'), $options['delay'])
        ->click('a.buttonNext')
        ->prestaShopSwitch('shipping_handling', !empty($options['handling']))
        ->prestaShopSwitch('is_free', !empty($options['free']));

        $based_on = empty($options['based_on']) ? 1 : ($options['based_on'] === 'price' ? 2 : 1);
        $browser->click('input[name="shipping_method"][value="'.$based_on.'"]');

        if (!empty($options['tax_rule']))
            $browser->select('#id_tax_rules_group', $options['tax_rule']);

        $index = 3;
        $inf = 0;
        if (isset($options['ranges'])) {
            foreach ($options['ranges'] as $upper_limit => $value) {
                if ($index > 3)
                    $browser->click('#add_new_range');

                // Set range inf
                $browser
                ->fillIn('tr.range_inf td:nth-child('.$index.') input', $inf)
                // Set range sup
                ->fillIn('tr.range_sup td:nth-child('.$index.') input', $upper_limit)
                // Check all zones
                ->checkbox('tr.fees_all td:nth-child(2) input', true)
                ->sleep(1);

                /*
                // Fill in price / weight
                ->fillIn('tr.fees_all td:nth-child('.$index.') input', $value)
                ->click('tr.range_inf');*/

                foreach ($browser->find('tr.fees td:nth-child('.$index.') input', ['unique' => false]) as $input) {
                    $browser->setElementValue($input, $value);
                }
                /*$spinner = new \PrestaShop\Helper\Spinner('Could not set price range.', 30);

                // We need this, because the Javascript populating the form is quite slow.
                $spinner->assertBecomesTrue(function() use ($browser, $index, $value) {
                    return $browser->getValue('tr:nth-last-child(2) td:nth-child('.$index.') input') == $value;
                });*/
                
                // $browser->waitForUserInput();

                $index += 1;
                $inf = $upper_limit;
            }
        }

        if (!empty($options['free']))
            $browser->checkbox('tr.fees_all td:nth-child(2) input', true);

        $oorb = isset($options['oorb']) ? ($options['oorb'] === 'highest' ? 0 : 1) : 0;
        $browser->select('#range_behavior', $oorb);

        $browser->click('a.buttonNext');

        $browser->sleep(1);

        $browser->click('a.buttonNext')
        ->prestaShopSwitch('active', true)
        ->click('a.buttonFinish')
        ->ensureStandardSuccessMessageDisplayed();
    }
}
