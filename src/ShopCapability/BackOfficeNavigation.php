<?php

namespace PrestaShop\ShopCapability;

class BackOfficeNavigation extends ShopCapability
{
	/**
	* Returns an array with controller names as key and URLs as values.
	* Assumes the browser is on a Back-Office page
	*/
	public function getMenuLinks()
	{
		$links = [];

		$browser = $this->getShop()->getBrowser();
		$maintabs = $browser->find('li.maintab', ['unique' => false]);
		foreach ($maintabs as $maintab)
		{
			echo "hi\n";
		}

		return $links;
	}

	/**
	* Logs in to the back-office.
	* Options may include: admin_email, admin_password, stay_logged_in
	*/
	public function login($options = [])
	{
		$options = OptionProvider::addDefaults('BackOfficeLogin', $options);

		$browser = $this->getShop()->getBrowser();
		$browser
		->visit($this->getShop()->getBackOfficeURL())
		->fillIn('#email', $options['admin_email'])
		->fillIn('#passwd', $options['admin_password'])
		->checkbox('#stay_logged_in', $options['stay_logged_in'])
		->click('button[name=submitLogin]')
		->ensureElementShowsUpOnPage('#maintab-AdminDashboard', 5);
	}
}
