<?php

namespace PrestaShop\PSTAF\PageObject;

class MyAddresses extends PageObject
{
    public function goToNewAddress()
    {
        $this->getBrowser()->clickFirstVisible('a.btn.button-medium');

        return $this->getPageObject('EditAddress');
    }

    public function hasAddressWithAlias($alias)
    {
    	try {
	    	foreach ($this->getBrowser()->find('h3.page-subheading', ['unique' => false]) as $heading) {
	    		$text = strtolower($heading->getText());
	    		if (strpos($text, strtolower($alias)) !== false) {
	    			return true;
	    		}
	    	}
	    } catch (\Exception $e) {
	    	return false;
	    }

    	return false;
    }

    public function editAddress($alias)
    {
    	$xpath = '//div[contains(@class,"address") and not(contains(@class, "addresses")) and //h3[contains(., "'.$alias.'")]]//a[not(contains(@href, "delete"))]';
		$this->getBrowser()->click('{xpath}'.$xpath)->waitFor('#alias');
		return $this->getPageObject('EditAddress');
    }

    public function deleteAddress($alias)
    {
    	$xpath = '//div[contains(@class,"address") and not(contains(@class, "addresses")) and //h3[contains(., "'.$alias.'")]]//a[contains(@href, "delete")]';

		// don't take screenshot, it fails if alert is open!
		$recordingScreenshots = $this->getBrowser()->getRecordScreenshots();

		try {
            $this->getBrowser()
            ->setRecordScreenshots(false)
            ->click('{xpath}'.$xpath)
            ->acceptAlert();
        } finally {
            // Whatever happens, set original recordScreenshots property back
            $this->getBrowser()
            ->setRecordScreenshots($recordingScreenshots);
        }

		sleep(1);

		if ($this->getBrowser()->hasVisible('{xpath}'.$xpath)) {
			throw new \Exception('It seems the address was not deleted.');
		}

		return $this;
    }
}
