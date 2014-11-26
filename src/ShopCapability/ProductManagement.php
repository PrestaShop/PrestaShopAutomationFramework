<?php

namespace PrestaShop\PSTAF\ShopCapability;

use PrestaShop\PSTAF\Helper\Spinner;

class ProductManagement extends ShopCapability
{

    private function saveProduct()
    {
        $browser = $this->getBrowser();

        $assert = new Spinner('Save button did not appear in time.', 30);
        $assert->assertBecomesTrue(function () use ($browser) {
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

        $saveSpinner = new Spinner('Could not save product.', 60, 2000);

        // Try this in a loop, because the javascript that populates link rewrite is
        // very unstable.
        $saveSpinner->assertNoException(function () use ($browser, $options) {
            $browser
            ->fillIn($this->i18nFieldName('#name'), $options['name'])
            ->sleep(5)
            ->click('#link-Prices')
            ->waitFor('#priceTE')
            ->fillIn('#priceTE', $options['price'])
            ->select('#id_tax_rules_group', empty($options['tax_rules_group']) ? 0 : $options['tax_rules_group']);

            $this->saveProduct();
        });

        if (isset($options['specific_price'])) {
            $browser
            ->click('#link-Prices')
            ->waitFor('#priceTE');

            $m = [];
            if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*%\s*$/', $options['specific_price'], $m)) {
                $percentage = $m[1];
            } else {
                throw new \Exception("Invalid specific price specified: {$options['specific_price']}.");
            }

            $browser
            ->click('#show_specific_price')
            ->select('#sp_reduction_type', 'percentage')
            ->fillIn('#sp_reduction', $percentage);

            $this->saveProduct();
        }

        if (!empty($options['quantity'])) {
            $browser
            ->click('#link-Quantities')
            ->waitFor('#qty_0')
            ->fillIn('#qty_0 input', $options['quantity']);

            $browser->executeScript('$("#qty_0 input").trigger("change");');

            $this->saveProduct();

            $browser
            ->click('#link-Quantities')
            ->waitFor('#qty_0');

            $spinner = new Spinner();

            $spinner->assertNoException(function () use ($browser, $options) {
                $a = (int) $this->i18nParse($browser->getValue('#qty_0 input'), 'float');
                $e = (int) $options['quantity'];

                if ($e !== $a)
                    throw new \PrestaShop\PSTAF\Exception\ProductCreationIncorrectException('quantity', $e, $a);
            });
        }

        $dimensions = ['width', 'height', 'depth', 'weight'];

        $onShippingTab = false;
        foreach ($dimensions as $dimension) {
            if (!empty($options[$dimension])) {
                if (!$onShippingTab) {
                    $browser->click('#link-Shipping')->waitFor("#$dimension");
                    $onShippingTab = true;
                }

                $browser->fillIn("#$dimension", $options[$dimension]);
            }
        }
        if ($onShippingTab) {
            $this->saveProduct();
        }

        $browser
        ->click('#link-Prices')
        ->waitFor('#priceTE');

        $expected_price = (float) $options['price'];
        $actual_price = $this->i18nParse($browser->getValue('#priceTE'));
        if ($actual_price !== $expected_price)
            throw new \PrestaShop\PSTAF\Exception\ProductCreationIncorrectException('price', $expected_price, $actual_price);

        $id_product = (int) $browser->getURLParameter('id_product');

        if ($id_product <= 0)
            throw new \PrestaShop\PSTAF\Exception\ProductCreationIncorrectException();

        return [
            'id' => $id_product,
            'fo_url' => $browser->getAttribute('#page-header-desc-product-preview', 'href')
        ];
    }
}
