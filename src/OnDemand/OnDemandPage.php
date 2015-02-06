<?php

namespace PrestaShop\PSTAF\OnDemand;

class OnDemandPage
{
	private $browser;
	private $secrets;

	public function __construct($browser_or_parent, array $secrets = array())
	{
		if ($browser_or_parent instanceof OnDemandPage) {
			$this->browser = $browser_or_parent->getBrowser();
			$this->secrets = $browser_or_parent->getSecrets();
		} else {
			$this->browser = $browser_or_parent;
			$this->secrets = $secrets;
		}
	}

	public function getBrowser()
	{
		return $this->browser;
	}

	public function getSecrets()
	{
		return $this->secrets;
	}

	public function confirmPassword()
	{
		$password = $this->getSecrets()['customer']['password'];
		
		$this->getBrowser()
		->fillIn('#modal_password_confirm', $password)
		->click('#confirm_pwd_action');
	}
}
