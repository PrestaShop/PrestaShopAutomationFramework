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
	* Mysql database name
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
	public function __construct($filesystem_path, $shop_settings, $seleniumPort)
	{
		$this->browser = new Browser($seleniumPort);

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

	public function getPDO()
	{
		static $pdo;

		try {
			if (!$pdo)
				$pdo = new \PDO('mysql:host='.$this->mysql_host.';port='.$this->mysql_port.';dbname='.$this->mysql_database,
					$this->mysql_user,
					$this->mysql_pass
				);
		} catch (\Exception $e) {
			$pdo = null;
		}

		return $pdo;
	}

	/**
	* Drop the database if it exists
	* @return true if the database existed, false otherwise
	*/
	public function dropDatabaseIfExists()
	{
		$h = $this->getPDO();
		if ($h) {
			$sql = 'DROP DATABASE `'.$this->mysql_database.'`';
			$res = $h->exec($sql);
			if (!$res) {
				throw new \Exception($h->errorInfo()[2]);
			}
			return true;
		}
		return false;
	}
}
