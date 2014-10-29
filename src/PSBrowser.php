<?php

namespace PrestaShop\PSTAF;

class PSBrowser extends Browser
{
	public function ensureStandardSuccessMessageDisplayed($error_explanation = null)
	{
	    try {
	    	$this->autoScreenshot(false);
	        $element = $this->find('div.alert.alert-success');
	    } catch (\Exception $e) {
	        throw new \PrestaShop\PSTAF\Exception\StandardSuccessMessageNotDisplayedException($error_explanation);
	    } finally {
	    	$this->autoScreenshot();
	    }

	    if (!$element->isDisplayed())
	        throw new \PrestaShop\PSTAF\Exception\StandardSuccessMessageNotDisplayedException($error_explanation);

	    return $this;
	}

	public function ensureStandardErrorMessageNotDisplayed($error_explanation = null)
	{
	    try {
	    	$this->autoScreenshot(false);
	        $this->find('div.alert.alert-error', ['wait' => false]);
	        throw new \PrestaShop\PSTAF\Exception\StandardErrorMessageDisplayedException($error_explanation);
	    } catch (\Exception $e) {
	        // That's expected :)
	    } finally {
	    	$this->autoScreenshot();
	    }

	    return $this;
	}

    /**
	* Check or uncheck a PrestaShop classical switch
	*/
    public function prestaShopSwitch($idWithoutHash, $yesno)
    {
    	try {
	    	$this->autoScreenshot(false);
	        $idWithoutHash = $idWithoutHash . ($yesno ? '_on' : '_off');
	        $this->click('label[for='.$idWithoutHash.']');
	    } finally {
	    	$this->autoScreenshot();
	    }

        return $this;
    }

    public function prestaShopSwitchValue($idWithoutHash)
    {
    	try {
    		$this->autoScreenshot(false);
        	return $this->find('#'.$idWithoutHash.'_on')->isSelected();
        } finally {
        	$this->autoScreenshot();
        }
    }
}