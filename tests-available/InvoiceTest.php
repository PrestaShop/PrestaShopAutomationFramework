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

		$shop->getCarrierManager()->createCarrier($scenario['carrier']);

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

		print_r($json);

		$browser->waitForUserInput();
	}
}