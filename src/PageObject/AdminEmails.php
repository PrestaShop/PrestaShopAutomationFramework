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

    /**
     * $options may contain:
     * - domain (PS_MAIL_DOMAIN)
     * - server (PS_MAIL_SERVER)
     * - port (PS_MAIL_SMTP_PORT)
     * - username (PS_MAIL_USER)
     * - password (PS_MAIL_PASSWD)
     * - encryption (PS_MAIL_SMTP_ENCRYPTION): off, ssl or tls
     *
     *
     */
    public function setSMTP(array $options)
    {
        $defaults = [
            'domain' => '',
            'server' => '',
            'port' => 25,
            'username' => '',
            'password' => '',
            'encryption' => 'tls'
        ];

        $options = array_merge($defaults, $options);

        $this->getBrowser()
        ->clickLabelFor('PS_MAIL_METHOD_2')
        ->fillIn('input[name="PS_MAIL_DOMAIN"]', $options['domain'])
        ->fillIn('input[name="PS_MAIL_SERVER"]', $options['server'])
        ->fillIn('input[name="PS_MAIL_SMTP_PORT"]', $options['port'])
        ->fillIn('input[name="PS_MAIL_USER"]', $options['username'])
        ->fillIn('input[name="PS_MAIL_PASSWD"]', $options['password'])
        ->select('select[name="PS_MAIL_SMTP_ENCRYPTION"]', $options['encryption'])
        ->click('#mail_fieldset_smtp button');

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
