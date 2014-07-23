<?php

namespace PrestaShop;

use \PrestaShop\Helper\FileSystem as FS;

class ShopManager
{
	protected $configuration_file_path;
	protected static $managers = [];

	public function __construct($configuration_file_path = null)
	{
		if ($configuration_file_path === null)
			$configuration_file_path = realpath(FS::join('.', 'pstaf.conf.json'));
		$this->configuration_file_path = $configuration_file_path;
	}

	public static function getInstance($configuration_file_path = null)
	{
		if (!isset(static::$managers[$configuration_file_path]))
			static::$managers[$configuration_file_path] = new static($configuration_file_path);

		return static::$managers[$configuration_file_path];
	}

	private function _getShop($suffix = null)
	{
		if (!$suffix)
			$suffix = '';

		$configuration = ConfigurationFile::getInstance($this->configuration_file_path);
		$seleniumPort = SeleniumManager::getMyPort();
		$seleniumHost = 'http://localhost:'.(int)$seleniumPort.'/wd/hub';
		$shopSettings = $configuration->get('shop');
		$seleniumSettings = ['host' => $seleniumHost];

		$shop = new Shop($shopSettings, $seleniumSettings);

		// Where the shop files should live
		$target_folder_name = basename($configuration->get('shop.filesystem_path')).$suffix;
		$target_path = FS::join(
			$configuration->get('shop.path_to_web_root'),
			$target_folder_name
		);
		
		// Move the files to their location if they are not already there
		if (!is_dir($target_path))
			$shop->getFileManager()->copyShopFilesTo($target_path);

		// Copy database if needed
		$new_mysql_database = null;
		if ($suffix !== '' && $shop->getDatabaseManager()->databaseExists())
		{
			$new_mysql_database = $shop->getMysqlDatabase().$suffix;
			$shop->getDatabaseManager()->duplicateDatabaseTo($new_mysql_database);
		}

		$new_front_office_url = preg_replace(
			'#/([^/]+)(?:/)?$#',
			'/'.$target_folder_name.'/',
			$configuration->get('shop.front_office_url')
		);

		$configuration->set('shop.filesystem_path', $target_path);
		if ($new_mysql_database)
		{
			$configuration->set('shop.mysql_database', $new_mysql_database);
		}
		$configuration->set('shop.front_office_url', $new_front_office_url);

		$shop = new Shop($configuration->get('shop'), $seleniumSettings);

		if ($new_mysql_database)
		{
			$shop->getFileManager()->updateSettingsIncIfExists([
				'_DB_NAME_' => $configuration->get('shop.mysql_database')
			]);
		}

		$old_folder = basename($configuration->get('shop.front_office_url'));

		if ($shop->getDatabaseManager()->databaseExists())
		{
			$shop->getDatabaseManager()->changeShopUrlPhysicalURI(
				"/$old_folder/",
				"/$target_folder_name/"
			);
		}

		return $shop;
	}

	public function getShop()
	{
		$shop = null;
		$parallel = getenv('TEST_TOKEN') !== false;

		if ($parallel)
		{
			$uid_lock_path = FS::join(dirname($this->configuration_file_path), 'pstaf.maxuid');
			
			$h = fopen($uid_lock_path, 'c+');
			if (!$h)
				throw new \Exception('Could not get pstaf.maxuid file.');
			flock($h, LOCK_EX);
			$uid = (int)file_get_contents($uid_lock_path) + 1;
			file_put_contents($uid_lock_path, $uid);
			fflush($h);
			flock($h, LOCK_UN);
			fclose($h);

			$uid_suffix = '_tmpshpcpy_'.$uid;
			
			$shop = $this->_getShop($uid_suffix);
			
			$shop->setTemporary(true);
		}
		else
		{
			$shop = $this->_getShop();
		}

		return $shop;
	}

	public function cleanUp(\PrestaShop\Shop $shop)
	{
		if ($shop->isTemporary())
		{
			$shop->getDatabaseManager()->dropDatabaseIfExists();
			$shop->getFileManager()->deleteAllFiles();
		}
		$shop->getBrowser()->quit();
	}
}