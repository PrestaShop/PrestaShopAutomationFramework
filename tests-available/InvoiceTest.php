<?php

namespace PrestaShop\FunctionalTest;

class InvoiceTest extends \PrestaShop\TestCase\TestCase
{

	public static function checkInvoiceCoherence($invoice)
	{
		$tax_amount = 0;
		// Check VAT total
		$breakdowns = ['product_tax_breakdown', 'shipping_tax_breakdown', 'ecotax_tax_breakdown', 'wrapping_tax_breakdown'];
		foreach ($breakdowns as $bd)
		{
			if (isset($invoice['tax_tab'][$bd]))
			{
				foreach ($invoice['tax_tab'][$bd] as $rate => $data)
				{
					$tax_amount += $data['total_amount'];
				}
			}
		}

		$actual_tax_amount = (float)$invoice['order']['total_paid_tax_incl'] - (float)$invoice['order']['total_paid_tax_excl'];

		if ("$tax_amount" != "$actual_tax_amount")
			throw new \PrestaShop\Exception\InvoiceIncorrectException(
				"Actual tax amount `$actual_tax_amount` differs from sum of VAT amount in the tax breakdown (`$tax_amount`)."
			);
	}

	public static function checkInvoiceJson($expected, $actual)
	{
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

		if (isset($expected['total']))
		{
			foreach ($expected['total'] as $e_key => $e_value)
			{
				$a_value = (float)$actual['order'][$total_mapping[$e_key]];
				if((float)$e_value !== $a_value)
					$errors[] = "Got `$a_value` instead of `$e_value` for `$e_key`.";
			}
		}

		if (isset($expected['tax']['products']))
		{	
			$a = [];

			foreach ($actual['tax_tab']['product_tax_breakdown'] as $a_rate => $a_amount)
				$a[(float)$a_rate] = (float)$a_amount['total_amount'];
		
			foreach ($expected['tax']['products'] as $e_rate => $e_amount)
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

		self::checkInvoiceCoherence($actual);

		if (!empty($errors))
			throw new \PrestaShop\Exception\InvoiceIncorrectException(implode("\n", $errors));
	}

	/**
	 * @dataProvider jsonExampleFiles
	 * @parallelize 4
	 */
	public function testInvoice($exampleFile)
	{
		$shop = static::getShop();
		$browser = $shop->getBrowser();

		$shop->getBackOfficeNavigator()->login();

		$scenario = $this->getJSONExample($exampleFile);

		if (isset($scenario['meta']['rounding_mode']))
			$shop->getPreferencesManager()->setRoundingMode($scenario['meta']['rounding_mode']);

		if (isset($scenario['meta']['rounding_type']))
			$shop->getPreferencesManager()->setRoundingType($scenario['meta']['rounding_type']);

		if (isset($scenario['meta']['tax_breakdown_on_invoices']))
			$shop->getTaxManager()->enableTaxBreakdownOnInvoices($scenario['meta']['tax_breakdown_on_invoices']);

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
			$shop
			->getPageObject('FrontOfficeProductSheet')
			->visit($data['info']['fo_url'])
			->setQuantity($data['quantity'])
			->addToCart();
		}

		$data = $shop->getCheckoutManager()->orderCurrentCartFiveSteps([
			'carrier' => $scenario['carrier']['name'],
			'payment' => 'bankwire'
		]);

		$cart_total = $data['cart_total'];
		$id_order = $data['id_order'];

		$json = $shop->getOrderManager()->visit($id_order)->validate()->getInvoiceFromJSON();

		if ($cart_total != $json['order']['total_paid_tax_incl'])
			throw new \Exception(
				"Cart total `$cart_total` differs from invoice total of `{$actual['order']['total_paid_tax_incl']}`."
			);

		self::checkInvoiceJson($scenario['expect']['invoice'], $json);

		// Now check that the invoice doesn't change if the rounding settings change!
		
		$modes = \PrestaShop\ShopCapability\PreferencesManagement::getRoundingModes();
		$types = \PrestaShop\ShopCapability\PreferencesManagement::getRoundingTypes();
		
		unset($modes[$scenario['meta']['rounding_mode']]);
		unset($types[$scenario['meta']['rounding_type']]);

		foreach ($modes as $mode => $unused)
		{	
			$shop->getPreferencesManager()->setRoundingMode($mode);
			foreach ($types as $type => $unused)
			{
				$shop->getPreferencesManager()->setRoundingType($type);
				$new_json = $shop->getOrderManager()
				->visit($id_order)
				->getInvoiceFromJSON();

				try {
					self::checkInvoiceJson($scenario['expect']['invoice'], $new_json);
				} catch (\Exception $e) {
					$message = sprintf(
						'Invoice is not the same after changing rounding mode from `%1$s` to `%2$s` and rounding type from `%3$s` to `%4$s`.',
						$scenario['meta']['rounding_mode'],
						$mode,
						$scenario['meta']['rounding_type'],
						$type
					);
					throw new \PrestaShop\Exception\FailedTestException($message."\n".$e->getMessage());
				}
			}
		}
	}
}