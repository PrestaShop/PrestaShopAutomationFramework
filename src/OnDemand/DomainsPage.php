<?php

namespace PrestaShop\PSTAF\OnDemand;

class DomainsPage extends OnDemandPage
{
	public function checkIfDomainIsAvailable($domain)
	{
		list($name, $tld) = explode('.', $domain, 2);
		$tld = ".$tld";

		$this->getBrowser()
		->click('#buyDomainLabel')
		->waitFor('#inputNameDomain')
		->fillIn('#inputNameDomain', $name)
		->select('#top-level-domain', $tld)
		->click('#check-domain-availability');

		try {
			$this->getBrowser()->waitFor(
				'{xpath}//form[contains(@action, "order_domain")]//button'
			);
			return true;
		} catch (\Exception $e) {
			if ($this->getBrowser()->hasVisible(
				'{xpath}//form[contains(@action, "associate_domain")]//button'
			)) {
				return false;
			} else {
				throw $e;
			}
		}
	}

	public function orderDomain()
	{
		$this->getBrowser()->click('{xpath}//form[contains(@action, "order_domain")]//button');
		if(strpos($this->getBrowser()->getCurrentURL(), 'init=address') !== false) {
			return new AddressFormPage($this);
		} else {
			throw new \Exception('Not Implemented Yet');
		}
	}
}