<?php

namespace PrestaShop\FunctionalTest;

class InvoiceTest extends \PrestaShop\TestCase\LazyTestCase
{
	public function getScenarios()
	{
		$scenarios = [];
		$src_dir = __DIR__.'/data/InvoiceTest';
		foreach (scandir($src_dir) as $entry)
			if (preg_match('/\.json$/', $entry))
				$scenarios[] = ["$src_dir/$entry"];

		return $scenarios;
	}

	/**
	 * @dataProvider getScenarios
	 */
	public function testInvoice($scenario_file)
	{
		$shop = static::getShop();
		$browser = $shop->getBrowser();

		$shop->getBackOfficeNavigator()->login();

		$scenario = json_decode(file_get_contents($scenario_file), true);

		if (isset($scenario['meta']['rounding_mode']))
			$shop->getPreferencesManager()->setRoundingMode($scenario['meta']['rounding_mode']);

		if (isset($scenario['meta']['rounding_type']))
			$shop->getPreferencesManager()->setRoundingType($scenario['meta']['rounding_type']);

		$shop->getCarrierManager()->createCarrier($scenario['carrier']);

		if (isset($scenario['discounts']))
		{
			foreach ($scenario['discounts'] as $name => $discount)
				$shop->getCartRulesManager()->createCartRule(['name' => $name, 'discount' => $discount]);
		}

		foreach ($scenario['products'] as $name => $data)
		{
			$tax_rules_group = 0;

			if (isset($data['vat']))
			{
				$vat = $data['vat'];

				// create the tax rules group but skip tax rules group checks, since they're awful slow, and
				// tax rules groups creation is already tested in a dedicated test case
				$tax_rules_group = $shop->getTaxManager()->getOrCreateTaxRulesGroupFromString($vat, true);
			}

			$data['tax_rules_group'] = $tax_rules_group;
			$data['name'] = $name;

			$scenario['products'][$name]['info'] = $shop->getProductManager()->createProduct($data);
		}

		$shop->getFrontOfficeNavigator()->login();

		foreach ($scenario['products'] as $name => $data)
		{
			$browser
			->visit($data['info']['fo_url'])
			->fillIn('#quantity_wanted', $data['quantity'])
			->click('#add_to_cart button');

			sleep(1);
		}

		$browser
		->visit($shop->getFrontOfficeURL())
		->click('div.shopping_cart a')
		->click('a.standard-checkout')
		->clickButtonNamed('processAddress')
		->clickLabelFor('cgv')
		->click('{xpath}//tr[contains(., "'.$scenario['carrier']['name'].'")]//input[@type="radio"]')
		->clickButtonNamed('processCarrier')
		->click('a.bankwire')
		->click('#center_column button[type="submit"]');

		$id_order = (int)$browser->getURLParameter('id_order');

		if ($id_order <= 0)
			throw new \Exception('Could not create order: no valid id_order found after payment.');

		$shop->getBackOfficeNavigator()
		->visit('AdminOrders', 'view', $id_order)
		->jqcSelect('#id_order_state', 2)
		->clickButtonNamed('submitState');

		$invoice_json_link = $browser->getAttribute('[data-selenium-id="view_invoice"]', 'href').'&debug=1';

		$browser->visit($invoice_json_link);

		$json = json_decode($browser->find('body')->getText(), true);

		$total_mapping = [
			'to_pay_tax_included' => 'total_paid_tax_incl',
			'to_pay_tax_excluded' => 'total_paid_tax_excl',
			'products_tax_included' => 'total_products_wt',
			'products_tax_excluded' => 'total_products',
			'shipping_tax_included' => 'total_shipping_tax_incl',
			'shipping_tax_excluded' => 'total_shipping_tax_excl',
			'discounts_tax_included' => 'total_discounts_tax_incl',
			'discounts_tax_excluded' => 'total_discounts_tax_excl',
			'wrapping_tax_included' => 'total_wrapping_tax_incl',
			'wrapping_tax_excluded' => 'total_wrapping_tax_excl'
		];

		$errors = [];

		if (isset($scenario['expect']['invoice']['total']))
		{
			foreach ($scenario['expect']['invoice']['total'] as $e_key => $e_value)
			{
				$a_value = (float)$json['order'][$total_mapping[$e_key]];
				if($e_value !== $a_value)
					$errors[] = "Got `$a_value` instead of `$e_value` for `$e_key`.";
			}
		}

		if (isset($scenario['expect']['invoice']['tax']['products']))
		{	
			$a = [];

			foreach ($json['tax_tab']['product_tax_breakdown'] as $a_rate => $a_amount)
				$a[(float)$a_rate] = (float)$a_amount['total_amount'];
		
			foreach ($scenario['expect']['invoice']['tax']['products'] as $e_rate => $e_amount)
			{
				$e_rate = (float)$e_rate;
				$e_amount = (float)$e_amount;

				if (!isset($a[$e_rate]) || $a[$e_rate] !== $e_amount)
				{
					$a_amount = isset($a[$e_rate]) ? $a[$e_rate] : null;
					$errors[] = "Invalid actual tax amount for rate `$e_rate`: got `$a_amount` instead of `$e_amount`.";
				}
			}
		}

		if (!empty($errors))
			throw new \PrestaShop\Exception\InvoiceIncorrectException(implode("\n", $errors));
	}
}