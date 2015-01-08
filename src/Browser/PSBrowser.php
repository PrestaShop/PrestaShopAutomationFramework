<?php

namespace PrestaShop\PSTAF\Browser;

use Exception;

use PrestaShop\PSTAF\Exception\StandardSuccessMessageNotDisplayedException;
use PrestaShop\PSTAF\Exception\StandardErrorMessageDisplayedException;

class PSBrowser extends Browser
{
	public function ensureStandardSuccessMessageDisplayed($error_explanation = null)
	{
		try {
			$this->waitFor('div.alert.alert-success');
		} catch (Exception $e) {
			throw new StandardSuccessMessageNotDisplayedException($error_explanation);
		}

	    return $this;
	}

	public function ensureStandardErrorMessageNotDisplayed($error_explanation = null)
	{
	    if ($this->hasVisible('div.alert.alert-error')) {
	    	throw new StandardErrorMessageDisplayedException($error_explanation);
	    }

		return $this;
	}

    /**
	* Check or uncheck a PrestaShop classical switch
	*/
    public function prestaShopSwitch($idWithoutHash, $yesno)
    {
    	return $this->clickLabelFor($idWithoutHash . ($yesno ? '_on' : '_off'));
    }

    public function prestaShopSwitchValue($idWithoutHash)
    {
    	return $this->find('#'.$idWithoutHash.'_on')->isSelected();
    }
}
