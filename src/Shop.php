<?php

namespace PrestaShop;

class Shop
{
	/**
	* Mysql host
	*/
	protected $mysql_host;

	/**
	* Mysql port
	*/
	protected $mysql_port;

	/**
	* Mysql user
	*/
	protected $mysql_user;

	/**
	* Mysql pass
	*/
	protected $mysql_pass;

	/**
	* Mysql database
	*/
	protected $mysql_database;

	/**
	* Mysql database prefix
	*/
	protected $database_prefix;

	/**
	* Physical location of the shop in the filesystem
	*/
	protected $filesystem_path;

	/**
	* Front-Office URL
	*/
	protected $front_office_url;

	/**
	* Name of the back-office folder (e.g. admin-dev)
	*/
	protected $back_office_folder_name;

	/**
	* Name of the back-office folder (e.g. admin-dev)
	*/
	protected $install_folder_name;

	/**
	* Version of the PrestaShop software
	*/
	protected $prestashop_version;

	/**
	* Shop Capabilities
	*/
	use \PrestaShop\Action\ShopInstallation;

	protected $browser;

	/**
	* Create a new shop object.
	* Settings come from the "shop" property of the configuration file.
	* Filesystem path is the root of the installation, e.g. /var/www/prestashop
	*/
	public function __construct($filesystem_path, $shop_settings)
	{
		$this->browser = new Browser();

		$import = [
			'mysql_host',
			'mysql_port',
			'mysql_user',
			'mysql_pass',
			'mysql_database',
			'database_prefix',
			'front_office_url',
			'back_office_folder_name',
			'install_folder_name',
			'prestashop_version'
		];

		foreach ($import as $prop)
		{
			if (isset($shop_settings[$prop]))
				$this->$prop = $shop_settings[$prop];
		}

		$this->filesystem_path = $filesystem_path;
	}

	/**
	* Get the installer URL
	*/
	public function getInstallerURL()
	{
		return rtrim($this->front_office_url, '/').'/'.trim($this->install_folder_name, '/').'/';
	}
}
