<?php

namespace PrestaShop\PSTAF\OnDemand;

class MyStoresPage extends OnDemandPage
{
	public function gotoDomains()
	{
		$this->getBrowser()->click('nav.menu[role=navigation] li:nth-of-type(2) a');

		return new DomainsPage($this);
	}

	public function gotoDetails($storeName = null)
	{
		$this
		->getStoreWidgetRoot($storeName)
		->find('{xpath}.//a[contains(@href, "init=manage") and descendant::img]')
		->click();

		return new StoreDetailsPage($this);
	}

	public function getStoreWidgetRoot($storeName = null)
	{
		if (null === $storeName) {
			return $this->getBrowser()->find('div.listingStore');
		}

		$xpath = '{xpath}//div[contains(@class, "listingStore") and .//h5[contains(., "' . $storeName . '")]]';
		return $this->getBrowser()->find($xpath);
	}

	public function getFrontOfficeURL($storeName)
	{
		return $this->getStoreWidgetRoot($storeName)->find('span.domain')->getText();
	}

	public function getBackOfficeURL($storeName)
	{
		return $this->getStoreWidgetRoot($storeName)->find('a[data-sel="bo-link"]')->getAttribute('href');
	}
}
