<?php

namespace PrestaShop\Action;

trait ShopInstallation
{
	public function install()
	{
		$this->browser
		->visit($this->getInstallerURL())
		->click('#btNext')
		->clickLabelFor('set_license')
		->click('#btNext')
		->fillIn('#infosShop', 'TOTOSHOP');


		$this->browser->waitForUserInput();
	}
}
