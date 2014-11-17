<?php

namespace PrestaShop\PSTAF\OnDemand;

class OnDemandPage
{
	private $browser;
	private $secrets;

	public function __construct($browser, array $secrets = array())
	{
		$this->browser = $browser;
		$this->secrets = $secrets;
	}

	public function getBrowser()
	{
		return $this->browser;
	}

	public function getSecrets()
	{
		return $this->secrets;
	}
}