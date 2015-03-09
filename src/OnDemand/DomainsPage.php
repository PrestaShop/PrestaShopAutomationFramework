<?php

namespace PrestaShop\PSTAF\OnDemand;

use Exception;

class DomainsPage extends OnDemandPage
{
	public function isDomainActive($domain)
	{
		$xpath = '{xpath}//tr[.//a[contains(@href, "' . $domain . '")]]';
		$row = $this->getBrowser()->find($xpath);
		try {
			$row->find('i.fa.fa-circle.green');
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function isGreen($domain)
	{
		try {
			$this->getDomainWidgetRoot($domain)->find('i.fa.green');
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	public function getDomainWidgetRoot($domain)
	{
		return $this->getBrowser()->find('{xpath}//tr[.//td[normalize-space(.) = "' . $domain . '"]]');
	}

	public function openDomainSettings($domain)
	{
		$root = $this->getDomainWidgetRoot($domain);
		$root->find('a[href="#"]')->click();

		return $root;
	}

	public function setPrimary($domain)
	{
		$this
		->openDomainSettings($domain)
		->find('{xpath}.//form[contains(@action, "set_default_domain")]//button')
		->click();

		$this->confirmPassword();

		return $this;
	}

	public function unAssign($domain)
	{
		$this
		->openDomainSettings($domain)
		->find('{xpath}.//form[contains(@action, "delete_domain")]//button')
		->click();

		$this->confirmPassword();

		try {
			$this->getDomainWidgetRoot($domain)->find('i.fa.fa-circle.orange');
		} catch (Exception $e) {
			throw new Exception('Domain `' . $domain . '` was not unassigned.');
		}

		return $this;
	}

	public function assignDomainToShop($domain, $shopName)
	{
		$this
		->openDomainSettings($domain)
		->find('{xpath}.//button[contains(., "' . $shopName . '")]')
		->click();

		$this->confirmPassword();

		return $this;
	}
}
