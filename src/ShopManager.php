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

	public function getShop()
	{
		$configuration = ConfigurationFile::getInstance($this->configuration_file_path);
		$seleniumPort = SeleniumManager::getMyPort();
		$seleniumHost = 'http://localhost:'.(int)$seleniumPort.'/wd/hub';

		$shopSettings = $configuration->get('shop');
		$seleniumSettings = ['host' => $seleniumHost];

		$shop = new Shop($shopSettings, $seleniumSettings);

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

			$new_mysql_database = $shop->getMysqlDatabase().$uid_suffix;

			$shop->getDatabaseManager()->duplicateDatabaseTo($new_mysql_database);
			$new_filesystem_path = $configuration->get('shop.filesystem_path').$uid_suffix;
			$shop->getFileManager()->copyShopFilesTo($new_filesystem_path);

			$settings_inc = FS::join($new_filesystem_path, 'config', 'settings.inc.php');
			if (file_exists($settings_inc))
			{
				$exp = '/(define\s*\(\s*([\'"])_DB_NAME_\2\s*,\s*([\'"]))(.*?)((\3)\s*\)\s*;)/';
				$settings = file_get_contents($settings_inc);
				$settings = preg_replace($exp, "\${1}".$new_mysql_database."\${5}", $settings);
				file_put_contents($settings_inc, $settings);
			}

			$new_front_office_url = preg_replace(
				'#/([^/]+)(?:/)?$#',
				'/\1'.$uid_suffix.'/',
				$configuration->get('shop.front_office_url')
			);

			$configuration->set('shop.filesystem_path', $new_filesystem_path);
			$configuration->set('shop.mysql_database', $new_mysql_database);
			$configuration->set('shop.front_office_url', $new_front_office_url);
			$shop = new Shop($configuration->get('shop'), $seleniumSettings);

			$old_folder = basename(dirname($this->configuration_file_path));
			$new_folder = $old_folder.$uid_suffix;
			$shop->getDatabaseManager()->changeShopUrlPhysicalURI("/$old_folder/", "/$new_folder/");
			$shop->setTemporary(true);
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