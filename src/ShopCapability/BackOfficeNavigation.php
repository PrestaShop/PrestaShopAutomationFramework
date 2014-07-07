<?php

namespace PrestaShop\ShopCapability;

use PrestaShop\OptionProvider;

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
			$as = $maintab->findElements(\WebDriverBy::tagName('a'));
			foreach ($as as $a)
			{
				$href = $a->getAttribute('href');
				$m = [];
				if (preg_match('/\?controller=(\w+)\b/', $href, $m))
				{
					$links[$m[1]] = $href;
				}
			}
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

	/**
	* Visit a controller page
	* e.g. AdminDashboard
	*
	* Preconditions: be on a back-office page
	*/
	public function visit($controller_name)
	{
		$links = $this->getMenuLinks();
		$browser = $this->getShop()->getBrowser();
		if (isset($links[$controller_name]))
		{
			return $browser->visit($links[$controller_name]);
		}
		else
			throw new \PrestaShop\Exception\AdminControllerNotFoundException();
	}
}
