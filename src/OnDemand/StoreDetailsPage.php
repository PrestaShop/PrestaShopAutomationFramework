<?php

namespace PrestaShop\PSTAF\OnDemand;

use Exception;

class StoreDetailsPage extends OnDemandPage
{
	public function checkIfDomainIsAvailable($domain)
	{
		list($name, $tld) = explode('.', $domain, 2);
		$tld = ".$tld";

		$this->getBrowser()
		->click('#buyDomainLabel')
		->waitFor('#buyDomainForm #inputNameDomain')
		->fillIn('#buyDomainForm #inputNameDomain', $name)
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
			throw new \Exception('Not Implemented Yet: Order domain while having an address already.');
		}
	}

	public function bindDomain($domain)
	{
		$url = $this->getBrowser()->getCurrentURL();

		$this->getBrowser()->click('{xpath}//a[contains(@href, "select_domain")]');

		$this->getBrowser()
		->click('#alreadyHaveDomainLabel')
		->fillIn('#alreadyHaveDomainForm [name="full_domain_name"]', $domain)
		->click('#alreadyHaveDomainForm button')
		;

		$this->getBrowser()->visit($url);

		return $this;
	}

	public function gotoNewFtpUserPage()
	{
		$this->getBrowser()->click('{xpath}//a[contains(@href, "init=ftp") and contains(@class, "btn")]');

		return new NewFTPUserPage($this);
	}

	public function isFtpAccountActive($name)
	{
		$selector = '{xpath}//a[contains(., "'.$name.'")]';
		return $this->getBrowser()->hasVisible($selector);
	}

	public function getFtpUserNameContaining($name)
	{
		return trim($this->getBrowser()->getText('{xpath}//a[contains(., "'.$name.'")]'));
	}

	public function isDomainActive($domainName)
	{
		return (new DomainsPage($this))->isDomainActive($domainName);
	}

	public function deleteStore()
	{
		$this->getBrowser()
		->click('#deleteThisStore')
		->fillIn('#confirmStoreDeletion', $this->getSecrets()['customer']['password'])
		->click('#submitStoreDeletion');

		if (!$this->getBrowser()->hasVisible('div.alert.alert-success')) {
			throw new Exception('Store doesn\'t seem to have been deleted.');
		}

		return new MyStoresPage($this);
	}
}
