<?php

namespace PrestaShop\PSTAF\PageObject;

use PrestaShop\PSTAF\Exception\FailedTestException;

class AdminEmails extends PageObject
{
    public function visit($url = null)
    {
        $this->getShop()->getBackOfficeNavigator()->visit('AdminEmails');

        return $this;
    }

    public function sendTestEmailTo($address)
    {
        $this->getBrowser()
        ->fillIn('#testEmail', $address)
        ->clickButtonNamed('btEmailTest');

        try {
            $this->getBrowser()->waitFor('#mailResultCheck.alert.alert-success', 15);
        } catch (\Exception $e) {
            throw new FailedTestException("Did not find successful confirmation of sent email.");
        }

        return $this;
    }
}
