<?php

namespace PrestaShop\PSTAF\ShopCapability;

use PrestaShop\PSTAF\OptionProvider;

class FrontOfficeNavigation extends ShopCapability
{
    public function setup()
    {

    }

    public function login($options = [])
    {
        $options = $this->getOptionProvider()->getValues('FrontOfficeLogin', $options);

        $browser = $this->getShop()->getBrowser();
        $browser
        ->visit($this->getShop()->getFrontOfficeURL())
        ->click('div.header_user_info a.login')
        ->waitFor('#email')
        ->fillIn('#email', $options['customer_email'])
        ->fillIn('#passwd', $options['customer_password'])
        ->click('#SubmitLogin');

        return $this;
    }

    public function visitHome()
    {
        return $this->getBrowser()->visit($this->getShop()->getFrontOfficeURL());
    }
}
