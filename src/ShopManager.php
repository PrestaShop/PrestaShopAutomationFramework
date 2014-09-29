<?php

namespace PrestaShop;

use \PrestaShop\Helper\FileSystem as FS;
use \djfm\Process\Process as Process;

class ShopManager
{
	private $configuration_file_path;
	private $conf;

	private static $managers = [];

	public function __construct($configuration_file_path)
	{
		$this->configuration_file_path = $configuration_file_path;
	}

	public static function getInstance($configuration_file_path = null)
	{
		if (!$configuration_file_path)
			$configuration_file_path = ConfigurationFile::getDefaultPath();

		if (!file_exists($configuration_file_path))
			throw new \Exception('Could not find configuration file pstaf.conf.json in current directory. Did you run pstaf project:init?');

		if (!isset(self::$managers[$configuration_file_path]))
			self::$managers[$configuration_file_path] = new static($configuration_file_path);

		return self::$managers[$configuration_file_path];
	}

	public function getWorkingDirectory()
	{
		return dirname($this->configuration_file_path);
	}

	private function rksort(array &$array)
	{
		ksort($array);
		foreach ($array as $key => $value)
		{
			if (is_array($value))
				$this->rksort($array[$key]);
		}
		return $array;
	}

	private function getInitialStateKey($initial_state)
	{	
		if ($initial_state === [] || (is_scalar($initial_state) && !$initial_state))
			$is = 'empty';
		else if (is_array($initial_state))
			$is = json_encode($this->rksort($initial_state));
		else if (is_scalar($initial_state))
			$is = $initial_state;
		else
			throw new \Exception('Invalid initial state.');

		return md5($is);
	}

	public function getUID()
	{
		$uid_lock_path = FS::join($this->getWorkingDirectory(), 'pstaf.maxuid.lock');

		$h = fopen($uid_lock_path, 'c+');
		if (!$h)
			throw new \Exception('Could not get pstaf.maxuid.lock file.');
		flock($h, LOCK_EX);
		$uid = (int)fgets($h) + 1;
		ftruncate($h, 0);
		rewind($h);
		fwrite($h, "$uid");
		fflush($h);
		flock($h, LOCK_UN);
		fclose($h);
		return $uid;
	}

	/**
	 * getShop uses the configuration file and 
	 * provided options to build a Shop ready for use by selenium
	 * scripts
	 * 
	 * @param  array  $options 
	 * @return a \PrestaShop\Shop instance
	 *
	 * $options is an array with the following keys:
	 * - initial_state: an array that will be passed to $shop->getFixtureManager()->setupInitialState(),
	 * 	 it is used to set the initial state of the shop for the test
	 * 	 
	 * - temporary: boolean, determines whether the shop is temporary or not.
	 *   a temporary shop is always a new shop, and will likely be destroyed at the end  of the tests.
	 *   if set to false, the shop is installed to path_to_web_root.
	 *   the target path will be deleted before installation and loaded from source again if it exists
	 *   AND if the overwrite option is set to true
	 *
	 * - overwrite: boolean, whether to overwrite or not the target shop files
	 *   when calling getShop with temporary === false - defaults to false
	 *
	 * - use_cache: whether or not the shop can be cached, i.e., installed once,
	 *   initialized with correct initial_state, and then restored from a copy of the files
	 *   and a dump of the database.
	 *   This is OK in most cases, but since there is some trickery involved in doing this
	 *   (replacing a few things in the DB, .htaccess file and config files)
	 *   it is not recommended to use the option in some scenarios, such as a complicated multishop
	 *   setup. 
	 * 
	 */
	public function getShop(array $options)
	{
		$inplace = getenv('PSTAF_INPLACE') === '1';

		$conf = new ConfigurationFile($this->configuration_file_path);

		$options['temporary'] = !empty($options['temporary']) && !$inplace;
		$options['overwrite'] = !empty($options['overwrite']);
		$options['use_cache'] = !empty($options['use_cache']) && !$inplace;
		
		if (!isset($options['initial_state']) || !is_array($options['initial_state']))
			$options['initial_state'] = [];

		// this may become a file resource
		// if it is, then we must close it and unlock it once
		// we're done.
		$lock = null;

		// whether or not we need to dump the constructed shop
		// if not null, it is the path where we need to put the files
		// after they have been built
		$dump_shop_to = null;

		// $using_cache will be true iff a warm cache exists
		// so when $dump_shop_to stays null
		$using_cache = false;

		// First we determine the path to the source files that
		// we're gonna use to build the shop.
		
		// this is the base case
		$source_files_path = $conf->getAsAbsolutePath('shop.filesystem_path');

		// if caching is allowed, we first check
		// whether the source files exist or not
		if ($options['use_cache'] && $options['initial_state'] !== [])
		{
			// since the source files may still be under build
			// we acquire a lock on a file that describes the initial state
			$initial_state_key = $this->getInitialStateKey($options['initial_state']);

			// acquire lock
			$lock_path = FS::join($this->getWorkingDirectory(), "pstaf.$initial_state_key.istate.lock");
			$lock = fopen($lock_path, 'w');
			if (!$lock)
				throw new \Exception(sprintf('Could not create lock file %s.', $lock_path));
			flock($lock, LOCK_EX);

			$cache_files_path = FS::join($this->getWorkingDirectory(), "pstaf.$initial_state_key.istate.shop");
			if (is_dir($cache_files_path))
			{
				// we're all set, release the lock and set source files path to the cached files
				flock($lock, LOCK_UN);
				fclose($lock);
				$lock = null;
				$source_files_path = $cache_files_path;
				$using_cache = true;
			}
			else
			{
				// well, we're out out of luck, we're going to need to build
				// the cache files ourselves
				// so we don't release the lock just yet, we'll do it when all
				// is nicely written
				// we remember this by setting $dump_shop_to to the path where cache files
				// will live
				$dump_shop_to = $cache_files_path;
				$using_cache = false;
			}
		}

		// At this point, we know where to take the files from, i.e.,
		// from $source_files_path
		// We now determine where to copy them to...
		
		$shop_name = basename($conf->getAsAbsolutePath('shop.filesystem_path'));
		
		// Temporary shop needs unique URL / folder-name
		if ($options['temporary'])
			$shop_name .= '_tmpshpcpy_'.$this->getUID();

		// Our shop URL
		$url = preg_replace('#[^/]+/?$#', $shop_name.'/', $conf->get('shop.front_office_url'));

		// Where the shop will live
		$target_files_path = FS::join(
			$conf->getAsAbsolutePath('shop.path_to_web_root'),
			$shop_name
		);

		$target_same_as_source = realpath($source_files_path) === realpath($target_files_path);

		// We say we are doing a new installation if the target files are not there
		$new_install = !file_exists($target_files_path);

		if (!$new_install && $options['overwrite'] && !$target_same_as_source)
		{
			FS::webRmR($target_files_path, $url);
			$new_install = true;
		}

		// Finally put the shop files in place!
		if (!$target_same_as_source && $new_install)
		{
			\PrestaShop\ShopCapability\FileManagement::copyShopFiles(
				$source_files_path,
				$target_files_path
			);
		}

		// Update the configuration with the new values
		$conf->set('shop.front_office_url', $url);
		$conf->set('shop.filesystem_path', $target_files_path);
		$conf->set('shop.mysql_database', $shop_name);

		// Prepare to fire up selenium
		$seleniumHost = 'http://localhost:'.(int)SeleniumManager::getMyPort().'/wd/hub';
		$seleniumSettings = ['host' => $seleniumHost];

		// Hoorah! Build our shop
		$shop = new \PrestaShop\Shop($conf->get('shop'), $seleniumSettings);


		if ($inplace && !$new_install)
		{
			// nothing for now
		}
		// if we're not using the cache, we need to setup the initial state
		// and maybe create a cache
		else if (!$using_cache && ($new_install || $options['overwrite']))
		{
			$shop->getFixtureManager()->setupInitialState($options['initial_state']);

			if ($dump_shop_to)
			{
				$shop->getFileManager()->copyShopFilesTo($dump_shop_to);
				$shop->getDatabaseManager()->dumpTo(FS::join($dump_shop_to, 'pstaf.shopdb.sql'));
			}

		}
		// otherwise, it's done, but we need to tune a few things
		else if ($using_cache)
		{
			$dump_path = FS::join($source_files_path, 'pstaf.shopdb.sql');
			if (file_exists($dump_path))
			{
				$shop->getDatabaseManager()
				->loadDump($dump_path)
				->changeShopUrlPhysicalURI("/$shop_name/");
			}

			$shop->getFileManager()
			->updateSettingsIncIfExists([
				'_DB_NAME_' => $conf->get('shop.mysql_database')
			])
			->changeHtaccessPhysicalURI("/$shop_name/");
		}

		$shop->setTemporary($options['temporary'] && !$inplace);

		if ($lock)
		{
			flock($lock, LOCK_UN);
			fclose($lock);
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

	public function cleanDirectory()
	{
		$home = $this->getWorkingDirectory();
		foreach (scandir($home) as $entry)
		{
			$path = FS::join($home, $entry);
			$m = [];
			if (preg_match('/pstaf\.(\w+)\.istate\.shop/', $entry, $m))
			{
				echo "Removing cached shop $entry...\n";
				FS::rmR($path);
				$lock = "pstaf.{$m[1]}.istate.lock";
				if (file_exists($lock))
				{
					echo "Removing useless lock file $lock...\n";
					unlink($lock);
				}
			}
			elseif (preg_match('/\.png$/', $entry))
			{
				echo "Removing screenshot $entry...\n";
				unlink($path);
			}
			elseif ($entry === 'selenium.log')
			{
				echo "Removing selenium log file $entry...\n";
				unlink($path);
			}
			elseif ($entry === 'php_errors.log')
			{
				echo "Removing local php error log file $entry...\n";
				unlink($path);
			}
			elseif ($entry === 'test-results')
			{
				echo "Removing test results folder $entry...\n";
				FS::rmR($path);
			}
			elseif ($entry === 'selenium.pid' && !\PrestaShop\SeleniumManager::isSeleniumStarted())
			{
				echo "Removing stale pid file $entry...\n";
				unlink($path);
			}
		}
	}

	public function updateRepo()
	{
		$conf = new ConfigurationFile($this->configuration_file_path);
		$repo = $conf->getAsAbsolutePath('shop.filesystem_path');

		if (0 !== (new Process('git', ['status']))->setWorkingDir($repo)->run(null, null, null, ['wait' => true]))
			echo "Not a github repository: $repo, so not updating.\n";
		else
		{
			$pulled = (new Process('git', ['pull']))
			->setWorkingDir($repo)
			->run(null, STDOUT, STDERR, ['wait' => true]);

			if ($pulled === 0)
				echo "Successfully updated repo!\n";
			else
				echo "Could not update repository, please check manually.\n";	
		} 
	}
}