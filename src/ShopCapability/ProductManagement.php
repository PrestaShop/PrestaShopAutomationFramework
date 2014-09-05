<?php

namespace PrestaShop\ShopCapability;

class ProductManagement extends ShopCapability
{

	private function saveProduct()
	{
		$browser = $this->getBrowser();

		$assert = new \PrestaShop\Helper\Spinner('Save button did not appear in time.', 10);
		$assert->assertBecomesTrue(function() use ($browser) {
			$browser->clickButtonNamed('submitAddproductAndStay');
			return true;
		});
		$browser->ensureStandardSuccessMessageDisplayed();
	}

	/**
	 * Create a product
	 * $options is an array with the the following keys:
	 * - name
	 * - price: price before tax
	 * - quantity: quantity (only regular stock, not advanced one)
	 * - tax_rules_group: id of the tax group to use for this product
	 *
	 * Returns an array with keys:
	 * - id: the id of the product
	 * - fo_url: the URL to access this product in FO
	 */
	public function createProduct($options)
	{
		$browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminProducts', 'new');

		$browser
		->fillIn($this->i18nFieldName('#name'), $options['name'])
		->click('#link-Prices')
		->waitFor('#priceTE')
		->fillIn('#priceTE', $options['price'])
		->select('#id_tax_rules_group', empty($options['tax_rules_group']) ? 0 : $options['tax_rules_group']);

		$this->saveProduct();

		if (!empty($options['quantity']))
		{
			$browser
			->click('#link-Quantities')
			->waitFor('#qty_0')
			->fillIn('#qty_0 input', $options['quantity']);

			sleep(1);

			$this->saveProduct();

			$browser
			->click('#link-Quantities')
			->waitFor('#qty_0');

			$a = (int)$this->i18nParse($browser->getValue('#qty_0 input'), 'float');
			$e = (int)$options['quantity'];

			if ($e !== $a)
				throw new \PrestaShop\Exception\ProductCreationIncorrectException('quantity', $e, $a);
		}

		$browser
		->click('#link-Prices')
		->waitFor('#priceTE');

		$expected_price = (float)$options['price'];
		$actual_price = $this->i18nParse($browser->getValue('#priceTE'));
		if ($actual_price !== $expected_price)
			throw new \PrestaShop\Exception\ProductCreationIncorrectException('price', $expected_price, $actual_price);

		$id_product = (int)$browser->getURLParameter('id_product');

		if ($id_product <= 0)
			throw new \PrestaShop\Exception\ProductCreationIncorrectException();

		return [
			'id' => $id_product,
			'fo_url' => $browser->getAttribute('#page-header-desc-product-preview', 'href')
		];
	}
}