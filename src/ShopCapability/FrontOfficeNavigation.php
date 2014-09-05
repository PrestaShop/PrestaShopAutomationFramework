<?php

namespace PrestaShop\ShopCapability;

use PrestaShop\OptionProvider;

class FrontOfficeNavigation extends ShopCapability
{
	public function setup()
	{
		
	}
	
	public function login($options = [])
	{
		$options = OptionProvider::addDefaults('FrontOfficeLogin', $options);

		$browser = $this->getShop()->getBrowser();
		$browser
		->visit($this->getShop()->getFrontOfficeURL())
		->click('div.header_user_info a.login')
		->fillIn('#email', $options['customer_email'])
		->fillIn('#passwd', $options['customer_password'])
		->click('#SubmitLogin');

		return $this;
	}
}
