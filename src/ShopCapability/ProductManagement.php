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
            ->click('#link-Informations')
            ->sleep(1)
            ->fillIn($this->i18nFieldName('#name'), $options['name'])
            ->sleep(1)
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
            ->waitFor('#qty_0');

            /**
             * Ok, so this next part is tricky!
             *
             * We need to detect the successful change of the quantity field.
             * So, we trigger the change by injecting javascript, and watch the DOM
             * to detect the success notification (div.growl.growl-notice).
             *
             * We need to watch the DOM before triggering the event, because to make
             * things easier, the notification is transient.
             *
             * This is a bit suboptimal because it fails to emulate exactly the user behaviour,
             * but it should be close enough. If anybody has a better idea, please PR!
             *
             */

$qset = <<<'EOS'
    var quantity = arguments[0];
    var done = arguments[1]; // Selenium wraps us inside a function, and we need to call done when done.
    var observer = new MutationObserver(function () {
        if ($('#growls .growl.growl-notice').length > 0) {
            done();
            observer.disconnect();
        }
    });
    observer.observe(document.documentElement, {childList: true, subtree: true});
    $("#qty_0 input").val(quantity);
    $("#qty_0 input").trigger("change");
EOS;

            try {
                $browser->setScriptTimeout(5);
                $spinner = new Spinner(null, 20, 5000);
                $spinner->assertNoException(function () use ($browser, $qset, $options) {
                    $browser->executeAsyncScript($qset, [$options['quantity']]);
                });
            } catch (\ScriptTimeoutException $e) {
                throw new \PrestaShop\PSTAF\Exception\ProductCreationIncorrectException('Could not set quantity.');
            }

            $this->saveProduct();

            $browser
            ->click('#link-Quantities')
            ->waitFor('#qty_0');

            $actualQuantity = (int) $this->i18nParse($browser->getValue('#qty_0 input'), 'float');
            $expectedQuantity = (int) $options['quantity'];

            if ($expectedQuantity !== $actualQuantity) {
                throw new \PrestaShop\PSTAF\Exception\ProductCreationIncorrectException('quantity', $expectedQuantity, $actualQuantity);
            }
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

        if ($id_product <= 0) {
            throw new \PrestaShop\PSTAF\Exception\ProductCreationIncorrectException();
        }

        return [
            'id' => $id_product,
            'fo_url' => $browser->getAttribute('#page-header-desc-product-preview', 'href')
        ];
    }
}
