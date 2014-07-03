<?php

namespace PrestaShop\ShopCapability;

class TaxManagement extends ShopCapability
{
	/**
	* Create a Tax Rule.
	*
	* Assumes:
	* - successfuly logged in to the back-office
	* - on a back-office page
	*
	* @return the id of the created tax rule
	*/
	public function createTaxRule($name, $rate, $enabled = true)
	{
		$shop = $this->getShop();

		$browser = $shop->getBackOfficeNavigator()->visit('AdminTaxes');

		$browser
		->click('#page-header-desc-tax-new_tax')
		->fillIn($this->i18nFieldName('#name'), $name)
		->fillIn('#rate', $rate)
		->prestaShopSwitch('active', $enabled)
		->click('#tax_form_submit_btn_1')
		->ensureStandardSuccessMessageDisplayed();

		$id_tax = $browser->getURLParameter('id_tax');

		if ((int)$id_tax < 1)
			throw new \PrestaShop\Exception\TaxRuleCreationIncorrectException();

		$check_url = \PrestaShop\Helper\URL::filterParameters(
			$browser->getCurrentURL(),
			['controller', 'id_tax', 'token'],
			['updatetax' => 1]
		);

		$browser->visit($check_url);

		$actual_name = $browser->getValue($this->i18nFieldName('#name'));
		$actual_rate = $this->i18nParse($browser->getValue('#rate'), 'float');
		$actual_enabled = $browser->prestaShopSwitchValue('active');

		if ($actual_name !== $name || $actual_rate !== (float)$rate || $actual_enabled != $enabled)
			throw new \PrestaShop\Exception\TaxRuleCreationIncorrectException();

		return (int)$id_tax;
	}
}
