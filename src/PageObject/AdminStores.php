<?php

namespace PrestaShop\PSTAF\PageObject;

use PrestaShop\PSTAF\Exception\FailedTestException;

class AdminStores extends PageObject
{
    public function visit($url = null)
    {
        $this->getShop()->getBackOfficeNavigator()->visit('AdminStores');

        return $this;
    }

    public function setShopEmail($email)
    {
        $this->getBrowser()
        ->fillIn('input[name="PS_SHOP_EMAIL"]', $email)
        ->click('#store_form button')
        ->ensureStandardSuccessMessageDisplayed();

        return $this;
    }
}
