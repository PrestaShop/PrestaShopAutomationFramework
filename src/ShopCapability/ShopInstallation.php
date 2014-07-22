<?php

namespace PrestaShop\ShopCapability;

use PrestaShop\OptionProvider;

class ShopInstallation extends ShopCapability
{
	public function install($options=[])
	{
		$options = OptionProvider::addDefaults('ShopInstallation', $options);

		$this->getShop()->getDatabaseManager()->dropDatabaseIfExists();

		$browser = $this->getShop()->getBrowser();

		$browser
		->visit($this->getShop()->getInstallerURL())
		->select('#langList', $options['language'])
		->click('#btNext')
		->checkbox('#set_license', true)
		->click('#btNext')
		->fillIn('#infosShop', $options['shop_name'])
		->jqcSelect('#infosActivity', $options['main_activity'])
		->jqcSelect('#infosCountry', $options['country'])
		->jqcSelect('#infosTimezone', $options['timezone'])
		->fillIn('#infosFirstname', $options['admin_firstname'])
		->fillIn('#infosName', $options['admin_lastname'])
		->fillIn('#infosEmail', $options['admin_email'])
		->fillIn('#infosPassword', $options['admin_password'])
		->fillIn('#infosPasswordRepeat', $options['admin_password'])
		->checkbox('#infosNotification', $options['newsletter'])
		->click('#btNext')
		->fillIn('#dbServer', $this->getShop()->getMysqlHost().':'.$this->getShop()->getMysqlPort())
		->fillIn('#dbName', $this->getShop()->getMysqlDatabase())
		->fillIn('#dbLogin', $this->getShop()->getMysqlUser())
		->fillIn('#dbPassword', $this->getShop()->getMysqlPass())
		->fillIn('#db_prefix', $this->getShop()->getDatabasePrefix())
		->click('#btTestDB')
		->waitFor('#btCreateDB')
		->click('#btCreateDB')
		->waitFor('#dbResultCheck.okBlock')
		->click('#btNext')
		->waitFor('a.BO', 600, 500);
	}
}
