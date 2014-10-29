<?php

namespace PrestaShop\PSTAF\PageObject;

class ShoppingCartSummary extends PageObject
{
    public static $setQuantityDefaults = [
        'id_product_attribute' => 0,
        'id_customization' => 0,
        'id_address_delivery' => 0
    ];

    public function visit($url = null)
    {
        $this->getBrowser()
        ->visit($this->getShop()->getFrontOfficeURL())
        ->click('div.shopping_cart a');

        return $this;
    }

    public function setQuantity($quantity, $id_product, $options = array())
    {
        $options = array_merge(static::$setQuantityDefaults, $options);
        $selector =  "quantity_{$id_product}_{$options['id_product_attribute']}"
                    ."_{$options['id_customization']}_{$options['id_address_delivery']}";

        $selector = "input[name='$selector']";

        $this
        ->getBrowser()
        ->fillIn($selector, $quantity);

        return $this;
    }

    public function getPercentReduction($id_product, $options = array())
    {
        $options = array_merge(static::$setQuantityDefaults, $options);
        $selector =  "product_price_{$id_product}_{$options['id_product_attribute']}"
                    ."_{$options['id_customization']}";

        $selector = "#$selector .price-percent-reduction";

        return trim($this->getBrowser()->getText($selector));
    }
}
