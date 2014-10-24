<?php

namespace PrestaShop;

class PSBrowser extends Browser
{
	public function ensureStandardSuccessMessageDisplayed($error_explanation = null)
	{
	    try {
	        $element = $this->find('div.alert.alert-success');
	    } catch (\Exception $e) {
	        throw new \PrestaShop\Exception\StandardSuccessMessageNotDisplayedException($error_explanation);
	    }

	    if (!$element->isDisplayed())
	        throw new \PrestaShop\Exception\StandardSuccessMessageNotDisplayedException($error_explanation);

	    return $this;
	}

	public function ensureStandardErrorMessageNotDisplayed($error_explanation = null)
	{
	    try {
	        $this->find('div.alert.alert-error', ['wait' => false]);
	        throw new \PrestaShop\Exception\StandardErrorMessageDisplayedException($error_explanation);
	    } catch (\Exception $e) {
	        // That's expected :)
	    }

	    return $this;
	}

    /**
	* Check or uncheck a PrestaShop classical switch
	*/
    public function prestaShopSwitch($idWithoutHash, $yesno)
    {
        $idWithoutHash = $idWithoutHash . ($yesno ? '_on' : '_off');
        $this->click('label[for='.$idWithoutHash.']');

        return $this;
    }

    public function prestaShopSwitchValue($idWithoutHash)
    {
        return $this->find('#'.$idWithoutHash.'_on')->isSelected();
    }
}