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

    public function modifyTranslations($type, $template, $language)
    {
        $b = $this->getBrowser();

        $b->select('#type', $type);

        if ($template) {
            $b->select('#theme', $template);
        } else {
            $b->select('#theme', "");
        }

        $b
        ->click('#language-button')
        ->click("#translations-languages [data-type=\"$language\"] a")
        ->click("#modify-translations");

        return $this;
    }

    public function getModuleEmailSubject($module, $email)
    {
        $b = $this->getBrowser();

        $b
        ->click('a[onclick="$(\'#'.$module.'\').slideToggle();"]')
        ->click("#$module .panel-title");

        return $b->getValue("#email-$email .label-subject input");
    }
}
