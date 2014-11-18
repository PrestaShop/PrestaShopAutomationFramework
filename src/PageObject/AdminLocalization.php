<?php

namespace PrestaShop\PSTAF\PageObject;

class AdminLocalization extends PageObject
{
    public function visit($url = null)
    {
        $this->getShop()->getBackOfficeNavigator()->visit('AdminLocalization');

        return $this;
    }

    public function getDefaultLanguageName()
    {
        return trim($this->getBrowser()->getSelectText('#PS_LANG_DEFAULT'));
    }

    public function getDefaultCountryId()
    {
        return trim($this->getBrowser()->getValue('#PS_COUNTRY_DEFAULT'));
    }

    public function getDefaultCountryName()
    {
        return trim($this->getBrowser()->getText('#PS_COUNTRY_DEFAULT_chosen'));
    }
}
