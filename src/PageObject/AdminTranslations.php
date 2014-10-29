<?php

namespace PrestaShop\PSTAF\PageObject;

class AdminTranslations extends PageObject
{
    public function visit($url = null)
    {
        $this->getShop()->getBackOfficeNavigator()->visit('AdminTranslations');

        return $this;
    }

    public function addOrUpdateLanguage($lc)
    {
        $this->getBrowser()
        ->jqcSelect('#params_import_language', $lc.'|'.$this->getShop()->getPrestaShopVersion())
        ->clickButtonNamed('submitAddLanguage')
        ->ensureStandardSuccessMessageDisplayed();

        return $this;
    }
}
