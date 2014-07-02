<?php

namespace PrestaShop\ShopCapability;

class DatabaseManagement extends ShopCapability
{
	public function getPDO()
	{
		static $pdo;

		$shop = $this->getShop();

		try {
			if (!$pdo)
				$pdo = new \PDO('mysql:host='.$shop->getMysqlHost().';port='.$shop->getMysqlPort().';dbname='.$shop->getMysqlDatabase(),
					$shop->getMysqlUser(),
					$shop->getMysqlPass()
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
			$sql = 'DROP DATABASE `'.$this->getShop()->getMysqlDatabase().'`';
			$res = $h->exec($sql);
			if (!$res) {
				throw new \Exception($h->errorInfo()[2]);
			}
			return true;
		}
		return false;
	}
}
