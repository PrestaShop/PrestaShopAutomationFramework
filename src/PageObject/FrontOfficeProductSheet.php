<?php

namespace PrestaShop\PSTAF\PageObject;

class FrontOfficeProductSheet extends PageObject
{
    public function visit($url = null)
    {
        $this->getBrowser()->visit($url);

        return $this;
    }

    public function setQuantity($q)
    {
        $this->getBrowser()
        ->waitFor('#quantity_wanted', 60)
        ->fillIn('#quantity_wanted', $q);

        return $this;
    }

    public function addToCart()
    {
        $this->getBrowser()->click('#add_to_cart button');

        try {
            $this->getBrowser()->click('#layer_cart span.cross');
        } catch (\Exception $e) {
            // we don't care, maybe the popin was disabled
        }

        return $this;
    }
}
