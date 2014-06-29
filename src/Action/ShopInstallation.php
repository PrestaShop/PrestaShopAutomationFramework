<?php

namespace PrestaShop\Action;

trait ShopInstallation
{
	public function install($options=[])
	{
		$options = \PrestaShop\Action\OptionProvider::addDefaults('ShopInstallation', $options);

		$this->dropDatabaseIfExists();

		$this->browser
		->visit($this->getInstallerURL())
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
		->fillIn('#dbServer', $this->mysql_host.':'.$this->mysql_port)
		->fillIn('#dbName', $this->mysql_database)
		->fillIn('#dbLogin', $this->mysql_user)
		->fillIn('#dbPassword', $this->mysql_pass)
		->fillIn('#db_prefix', $this->database_prefix)
		->click('#btTestDB')
		->waitFor('#btCreateDB')
		->click('#btCreateDB')
		->waitFor('#dbResultCheck.okBlock')
		->click('#btNext')
		->waitFor('a.BO', 600, 500)
		->click('a.FO');
	}
}
