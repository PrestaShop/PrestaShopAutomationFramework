<?php

namespace PrestaShop\PSTAF\OnDemand;

use Exception;

class SettingsPage extends OnDemandPage
{
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
		$row = $this->getBrowser()->find('{xpath}//tr[.//a[contains(@href, "' . $domainName . '")]]');
		try {
			$row->find('i.fa.fa-circle.green');
			return true;
		} catch (Exception $e) {
			return false;
		}
	}
}
