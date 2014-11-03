<?php

namespace PrestaShop\PSTAF\ShopCapability;

use PrestaShop\PSTAF\OptionProvider;

class Registration extends ShopCapability
{
    public function registerCustomer(array $options = array())
    {
        $options = $this->getOptionProvider()->getValues('CustomerRegistration', $options);

        $browser = $this->getBrowser();

        $this->getShop()->getFrontOfficeNavigator()->visitHome();

        $gender = $options['customer_gender'] === 'female' ? 2 : 1;

        $browser
        ->click('a.login')
        ->fillIn('#email_create', $options['customer_email'])
        ->click('#SubmitCreate')
        ->clickLabelFor('id_gender'.$gender)
        ->fillIn('#customer_firstname', $options['customer_firstname'])
        ->fillIn('#customer_lastname', $options['customer_lastname'])
        ->fillIn('#passwd', $options['customer_password'])
        ->select('#days', $options['customer_birthdate']['day'])
        ->select('#months', $options['customer_birthdate']['month'])
        ->select('#years', $options['customer_birthdate']['year']);

        if ($options['newsletter']) {
            $browser->clickLabelFor('newsletter');
        }

        if ($options['partners']) {
            $browser->clickLabelFor('optin');
        }

        $browser
        ->click('#submitAccount')
        ->waitFor('p.alert.alert-success', 0);

        return $this;
    }
}
