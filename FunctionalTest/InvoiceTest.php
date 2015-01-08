<?php

namespace PrestaShop\PSTAF\FunctionalTest;

use PrestaShop\PSTAF\TestCase\TestCase;
use PrestaShop\PSTAF\ShopCapability\PreferencesManagement;

class InvoiceTest extends TestCase
{

    public static function checkInvoiceCoherence($invoice)
    {
        $tax_amount = 0;
        // Check VAT total
        $breakdowns = ['product_tax_breakdown', 'shipping_tax_breakdown', 'ecotax_tax_breakdown', 'wrapping_tax_breakdown'];
        foreach ($breakdowns as $bd) {
            if (isset($invoice['tax_tab'][$bd])) {
                foreach ($invoice['tax_tab'][$bd] as $data) {
                    $tax_amount += $data['total_amount'];
                }
            }
        }

        $actual_tax_amount = (float) $invoice['order']['total_paid_tax_incl'] - (float) $invoice['order']['total_paid_tax_excl'];

        if ("$tax_amount" != "$actual_tax_amount")
            throw new \PrestaShop\PSTAF\Exception\InvoiceIncorrectException(
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

        if (isset($expected['total'])) {
            foreach ($expected['total'] as $e_key => $e_value) {
                $a_value = (float) $actual['order'][$total_mapping[$e_key]];
                if((float) $e_value !== $a_value)
                    $errors[] = "Got `$a_value` instead of `$e_value` for `$e_key`.";
            }
        }

        if (isset($expected['tax']['products'])) {
            $a = [];

            foreach ($actual['tax_tab']['product_tax_breakdown'] as $a_rate => $a_amount)
                $a[(float) $a_rate] = (float) $a_amount['total_amount'];

            foreach ($expected['tax']['products'] as $e_rate => $e_amount) {
                $e_rate = (float) $e_rate;
                $e_amount = (float) $e_amount;

                if (!isset($a[$e_rate]) || $a[$e_rate] !== $e_amount) {
                    $a_amount = isset($a[$e_rate]) ? $a[$e_rate] : null;
                    $errors[] = "Invalid actual tax amount for rate `$e_rate`: got `$a_amount` instead of `$e_amount`.";
                }
            }
        }

        if (isset($expected['tax']['shipping'])) {
            $a = [];

            foreach ($actual['tax_tab']['shipping_tax_breakdown'] as $data) {
                $a[(float) $data['rate']] = (float) $data['total_amount'];
            }

            foreach ($expected['tax']['shipping'] as $e_rate => $e_amount) {
                $e_rate = (float) $e_rate;
                $e_amount = (float) $e_amount;

                if (!isset($a[$e_rate]) || $a[$e_rate] !== $e_amount) {
                    $a_amount = isset($a[$e_rate]) ? $a[$e_rate] : null;
                    $errors[] = "Invalid actual shipping tax amount for rate `$e_rate`: got `$a_amount` instead of `$e_amount`.";
                }
            }
        }

        if (!empty($errors))
            throw new \PrestaShop\PSTAF\Exception\InvoiceIncorrectException(implode("\n", $errors));

        self::checkInvoiceCoherence($actual);
    }

    public static function runScenario($shop, array $scenario)
    {
        $output = ['pdf' => null, 'json' => null, 'jsonString' => null];

        $shop->getBackOfficeNavigator()->login();

        if (isset($scenario['meta']['rounding_mode'])) {
            $shop->getPreferencesManager()->setRoundingMode($scenario['meta']['rounding_mode']);
        }

        if (isset($scenario['meta']['rounding_type'])) {
            $shop->getPreferencesManager()->setRoundingType($scenario['meta']['rounding_type']);
        }

        if (isset($scenario['meta']['rounding_decimals'])) {
            $shop->getPreferencesManager()->setRoundingDecimals($scenario['meta']['rounding_decimals']);
        }


        if (isset($scenario['meta']['tax_breakdown_on_invoices'])) {
            $shop->getTaxManager()->enableTaxBreakdownOnInvoices($scenario['meta']['tax_breakdown_on_invoices']);
        }

        $carrier = $scenario['carrier'];

        if (isset($carrier['vat'])) {
            $carrier['tax_rule'] = $shop
                                    ->getTaxManager()
                                    ->getOrCreateTaxRulesGroupFromString($carrier['vat'], true);

            unset($carrier['vat']);
        }

        if (isset($carrier['price'])) {
            $carrier['ranges'] = [1000 => $carrier['price']];
            $carrier['free'] = false;
            $carrier['oorb'] = 'highest';
            unset($carrier['price']);
        }
        $shop->getCarrierManager()->createCarrier($carrier);

        foreach ($scenario['products'] as $name => $data) {
            $tax_rules_group = 0;

            if (isset($data['vat'])) {
                $vat = $data['vat'];

                // create the tax rules group but skip tax rules group checks, since they're awful slow, and
                // tax rules groups creation is already tested in a dedicated test case
                $tax_rules_group = $shop->getTaxManager()->getOrCreateTaxRulesGroupFromString($vat, true);
            }

            $data['tax_rules_group'] = $tax_rules_group;
            $data['name'] = $name;

            $scenario['products'][$name]['info'] = $shop->getProductManager()->createProduct($data);
        }

        if (isset($scenario['discounts'])) {
            foreach ($scenario['discounts'] as $name => $discount) {
                if (is_string($discount)) {
                    $discount = ['name' => $name, 'discount' => $discount];
                } else {
                    $discount['name'] = $name;
                    if (isset($discount['apply_to_product']) && !is_int($discount['apply_to_product'])) {
                        // apply_to_product may be specified with the product name,
                        // but createCartRule expects an id_product
                        $discount['apply_to_product'] = $scenario['products'][$discount['apply_to_product']]['info']['id'];
                    }
                }

                $shop->getCartRulesManager()->createCartRule($discount);
            }
        }

        $shop->getFrontOfficeNavigator()->login();

        foreach ($scenario['products'] as $name => $data) {
            $shop
            ->getPageObject('FrontOfficeProductSheet')
            ->visit($data['info']['fo_url'])
            ->setQuantity($data['quantity'])
            ->addToCart();

            sleep(5);
        }

        $data = $shop->getCheckoutManager()->orderCurrentCartFiveSteps([
            'carrier' => $scenario['carrier']['name'],
            'payment' => 'bankwire'
        ]);

        $output['cart_total'] = $data['cart_total'];
        $output['id_order'] = $data['id_order'];

        $orderPage = $shop->getOrderManager()->visit($output['id_order'])->validate();
        $output['pdf'] = $orderPage->getInvoicePDFData();
        $json = $orderPage->getInvoiceFromJSON();
        $output['json'] = $json;
        $output['jsonString'] = json_encode($json, JSON_PRETTY_PRINT);

        return $output;
    }

    /**
	 * @dataProvider jsonExampleFiles
	 * @parallelize 4
	 */
    public function testInvoice($exampleFile)
    {
        $shop = $this->shop;

        $scenario = $this->getJSONExample($exampleFile);

        $output = static::runScenario($shop, $scenario);

        if ($output['pdf']) {
            $this->writeArtefact(basename($exampleFile, '.json').'.pdf', $output['pdf']);
        }

        if ($output['jsonString']) {
            $this->writeArtefact(basename($exampleFile, '.json').'.invoice.json', $output['jsonString']);
        }

        self::checkInvoiceJson($scenario['expect']['invoice'], $output['json']);

        /* Temporarily disabled - unsafe check
        if ($output['cart_total'] != $output['json']['order']['total_paid_tax_incl']) {
            throw new \PrestaShop\PSTAF\Exception\FailedTestException(
                "Cart total `{$output['cart_total']}` differs from invoice total of `{$output['json']['order']['total_paid_tax_incl']}`."
            );
        }*/

        // Now check that the invoice doesn't change if the rounding settings change!

        $modes = PreferencesManagement::getRoundingModes();
        $types = PreferencesManagement::getRoundingTypes();

        unset($modes[$scenario['meta']['rounding_mode']]);
        unset($types[$scenario['meta']['rounding_type']]);

        foreach ($modes as $mode => $unused) {
            $shop->getPreferencesManager()->setRoundingMode($mode);
            foreach ($types as $type => $unused) {
                $shop->getPreferencesManager()->setRoundingType($type);
                $new_json = $shop->getOrderManager()
                ->visit($output['id_order'])
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
                    throw new \PrestaShop\PSTAF\Exception\FailedTestException($message."\n".$e->getMessage());
                }
            }
        }
    }
}
