<?php

namespace PrestaShop;

use \PrestaShop\Helper\FileSystem as FS;

class ShopManager
{
	public static function getShop($configuration_file_path = null)
	{
		$configuration = ConfigurationFile::getInstance($configuration_file_path);
		$seleniumPort = SeleniumManager::getMyPort();
		$seleniumHost = 'http://localhost:'.(int)$seleniumPort.'/wd/hub';

		$shopSettings = $configuration->get('shop');
		$seleniumSettings = ['host' => $seleniumHost];

		$shop = new Shop($shopSettings, $seleniumSettings);

		$parallel = true;

		if ($parallel)
		{
			$uid_lock_path = FS::join(basename($configuration_file_path), 'pstaf.maxuid');
			
			$h = fopen($uid_lock_path, 'c+');
			if (!$h)
				throw new \Exception('Could not get pstaf.maxuid file.');
			flock($h, LOCK_EX);

			$uid = ((int)trim(fgets($h))) + 1;
			ftruncate($h, 0);
			fputs($h, (string)$uid);
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
				$settings = preg_replace($exp, '\1'.$new_mysql_database.'\5', $settings);
				file_put_contents($settings_inc, $settings);
			}

			$configuration->set('shop.filesystem_path', $new_filesystem_path);
			$configuration->set('shop.mysql_database', $new_mysql_database);
			$shop = new Shop($configuration->get('shop'), $seleniumSettings);
		}


		return $shop;
	}
}