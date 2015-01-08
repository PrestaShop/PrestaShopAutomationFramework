<?php

namespace PrestaShop\PSTAF\ShopCapability;

use Exception;
use PrestaShop\PSTAF\Helper\Spinner;

class CartRulesManagement extends ShopCapability
{
    /**
	 * Create a Cart Rule
	 *
	 * $options may contain
	 * - name
	 * - discount: e.g. 10 %, 10 before tax, 10 after tax
	 * - free_shipping: boolean
	 */
    public function createCartRule(array $options)
    {
        $shop = $this->getShop();
        $browser = $this->getBrowser();

        $shop->getBackOfficeNavigator()->visit('AdminCartRules', 'new');

        $browser
        ->fillIn($this->i18nFieldName('#name'), $options['name'])
        ->click('#cart_rule_link_actions');

        $m = [];

        if (isset($options['discount'])) {
            if (preg_match('/^\s*(\d+(?:.\d+)?)\s*((?:%|before|after))/', $options['discount'], $m)) {
                $amount = $m[1];
                $type = $m[2];

                if ($type === '%') {
                    $browser
                    ->clickLabelFor('apply_discount_percent')
                    ->waitFor('#reduction_percent')
                    ->fillIn('#reduction_percent', $amount);
                } else {
                    $browser
                    ->clickLabelFor('apply_discount_amount')
                    ->waitFor('#reduction_amount')
                    ->fillIn('#reduction_amount', $amount)
                    ->select('select[name="reduction_tax"]', ['before' => 0, 'after' => 1][$type]);
                }
            } else
                throw new \Exception("Incorrect discount spec: {$options['discount']}.");
        }

        if (isset($options['free_shipping'])) {
            $browser->prestaShopSwitch('free_shipping', $options['free_shipping']);
        }

        if (isset($options['apply_to_product'])) {
            /**
             * This is not how a user would do it, but JQuery autocomplete
             * will NOT be triggered if the window doesn't have focus,
             * so unfortunately we can't rely on it.
             */
            $browser
            ->clickLabelFor('apply_discount_to_product')
            ->executeScript('$("#reduction_product").val(arguments[0])', [$options['apply_to_product']]);
        }

        $browser
        ->click('#desc-cart_rule-save-and-stay')
        ->ensureStandardSuccessMessageDisplayed();

        $id_cart_rule = (int) $browser->getURLParameter('id_cart_rule');

        if ($id_cart_rule <= 0)
            throw new \PrestaShop\PSTAF\Exception\CartRuleCreationIncorrectException();

        return $id_cart_rule;
    }
}
