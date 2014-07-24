<?php

namespace PrestaShop;

use \PrestaShop\Helper\FileSystem as FS;

class ShopManager
{
	protected $configuration_file_path;
	protected static $managers = [];

	public function __construct($configuration_file_path)
	{
		$this->configuration_file_path = $configuration_file_path;
	}

	public static function getInstance($configuration_file_path = null)
	{
		if ($configuration_file_path === null)
			$configuration_file_path = realpath(FS::join('.', 'pstaf.conf.json'));

		if (!$configuration_file_path)
			throw new \Exception('Could not find configuration file pstaf.conf.json in current directory. Did you run pstaf project:init?');

		if (!isset(static::$managers[$configuration_file_path]))
			static::$managers[$configuration_file_path] = new static($configuration_file_path);

		return static::$managers[$configuration_file_path];
	}

	public function getWorkingDirectory()
	{
		return dirname($this->configuration_file_path);
	}

	private function _getShop($options)
	{
		$suffix = $options['suffix'];

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
		{
			$shop->getFileManager()->copyShopFilesTo(
				$target_path,
				isset($options['source_files']) ? $options['source_files'] : null
			);
		}

		// Copy database if needed
		$new_mysql_database = null;
		if ($suffix !== '')
		{
			$new_mysql_database = $shop->getMysqlDatabase().$suffix;
			if (!isset($options['database_dump']))
			{
				if ($shop->getDatabaseManager()->databaseExists())
				{
					$shop->getDatabaseManager()->duplicateDatabaseTo($new_mysql_database);
				}
			}
			else
			{
				$shop->getDatabaseManager()->loadDump($options['database_dump'], $new_mysql_database);				
			}
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

		if ($shop->getDatabaseManager()->databaseExists())
		{
			$shop->getDatabaseManager()->changeShopUrlPhysicalURI("/$target_folder_name/");
		}

		$shop->getFileManager()->changeHtaccessPhysicalURI("/$target_folder_name/");

		return $shop;
	}

	private function rksort(array &$array)
	{
		ksort($array);
		foreach ($array as $key => $value)
		{
			if (is_array($value))
				$this->rksort($array[$key]);
		}
	}

	public function getUID()
	{
		$uid_lock_path = FS::join($this->getWorkingDirectory(), 'pstaf.maxuid');
			
		$h = fopen($uid_lock_path, 'c+');
		if (!$h)
			throw new \Exception('Could not get pstaf.maxuid file.');
		flock($h, LOCK_EX);
		$uid = (int)file_get_contents($uid_lock_path) + 1;
		file_put_contents($uid_lock_path, $uid);
		fflush($h);
		flock($h, LOCK_UN);
		fclose($h);

		return $uid;
	}

	public function buildInitialStateOrWaitForIt($options, $initial_state_key, $initial_state)
	{
		$lock_path = FS::join($this->getWorkingDirectory(), "pstaf.$initial_state_key.lock");
		$build_folder = FS::join($this->getWorkingDirectory(), "pstaf.$initial_state_key.shop");
		$database_dump = FS::join($build_folder, 'pstaf.shopdb.sql');

		$h = fopen($lock_path, 'w');
		if (!$h)
		{
			throw new \Exception(sprintf('Could not create lock file %s.', $lock_path));
		}
		flock($h, LOCK_EX);

		$shop = null;

		// shop already built
		if (is_dir($build_folder))
		{
			$options['source_files'] = $build_folder;
			$options['database_dump'] = $database_dump;
			$shop = $this->_getShop($options);
		}
		else
		{
			$shop = $this->_getShop($options);
			$shop->getFixtureManager()->setupInitialState($initial_state);
			$shop->getFileManager()->copyShopFilesTo($build_folder);
			$shop->getDatabaseManager()->dumpTo($database_dump);
		}
		flock($h, LOCK_UN);
		fclose($h);

		return $shop;
	}

	public function getShop(array $initial_state = null, $copy = true, $use_cache = true)
	{
		$tmp = is_array($initial_state) ? $initial_state : [];
		$this->rksort($tmp);
		$initial_state_key = empty($tmp) ? null : md5(json_encode($tmp));
		
		$options = ['suffix' => ''];

		if ($copy)
		{
			$options['suffix'] = '_tmpshpcpy_'.$this->getUID();
		}

		if ($initial_state_key)
		{
			if ($use_cache)
			{
				$shop = $this->buildInitialStateOrWaitForIt($options, $initial_state_key, $initial_state);
			}
			else
			{
				$shop = $this->_getShop($options);
				$shop->getFixtureManager()->setupInitialState($initial_state);
			}
		}
		else
		{
			$shop = $this->_getShop($options);
		}

		if ($copy)
		{
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