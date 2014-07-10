<?php

namespace PrestaShop\PageObject;

class AdminTranslations extends PageObject
{
	public function visit()
	{
		$this->getShop()->getBackOfficeNavigator()->visit('AdminTranslations');
	}

	public function addOrUpdateLanguage($lc)
	{
		$this->getBrowser()
		->jqcSelect('#params_import_language', $lc.'|'.$this->getShop()->getPrestaShopVersion())
		->clickButtonNamed('submitAddLanguage')
		->ensureStandardSuccessMessageDisplayed();
	}
}