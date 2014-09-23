<?php

namespace PrestaShop\ShopCapability;

use djfm\Process\Process;

class DatabaseManagement extends ShopCapability
{
	private $pdo;

	public function getPDO()
	{
		$shop = $this->getShop();

		try {
			if (!$this->pdo)
				$this->pdo = new \PDO('mysql:host='.$shop->getMysqlHost().';port='.$shop->getMysqlPort().';dbname='.$shop->getMysqlDatabase(),
					$shop->getMysqlUser(),
					$shop->getMysqlPass()
				);
		} catch (\Exception $e) {
			$this->pdo = null;
		}

		return $this->pdo;
	}

	public function databaseExists($database_name = null)
	{
		
		if ($database_name === null)
			$database_name = $this->getShop()->getMysqlDatabase();

		$h = $this->getPDO();
		if (!$h)
			return false;

		
		$sql = 'SHOW DATABASES LIKE \''.$database_name.'\'';
		$stm = $h->prepare($sql);
		$stm->execute();
		$res = $stm->fetchAll();

		return $res && count($res) === 1;
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
			return $res;
		}
		return false;
	}

	public function buildMysqlCommand($command, array $arguments)
	{
		$options = [
			'-u' => $this->getShop()->getMysqlUser(),
			'-h' => $this->getShop()->getMysqlHost(),
			'-P' => $this->getShop()->getMysqlPort(),
		];

		if ($this->getShop()->getMysqlPass())
		{
			$options['-p'] = $this->getShop()->getMysqlPass();
		}

		

		return new Process($command, $arguments, $options, ['wait' => true]);
	}

	public function duplicateDatabaseTo($new_database_name)
	{
		$old_database_name = $this->getShop()->getMysqlDatabase();

		$this->buildMysqlCommand('mysqladmin', ['create', $new_database_name])->run();
		Process::pipe(
			$this->buildMysqlCommand('mysqldump', [$old_database_name]),
			$this->buildMysqlCommand('mysql', [$new_database_name])
		);
	}

	public function dumpTo($path)
	{
		$command = $this->buildMysqlCommand('mysqldump', [$this->getShop()->getMysqlDatabase(), '>', $path]);
		$command->run();
	}

	public function loadDump($dump_path, $database_name = null)
	{
		if (!$database_name)
			$database_name = $this->getShop()->getMysqlDatabase();

		if (!$this->databaseExists($database_name))
		{
			$command = $this->buildMysqlCommand('mysqladmin', ['create', $database_name]);
			$command->run();
		}

		$command = $this->buildMysqlCommand('mysql', [$database_name, '<', $dump_path]);
		$command->run();

		return $this;
	}

	public function changeShopUrlPhysicalURI($new_physical_uri)
	{
		$h = $this->getPDO();

		$sql = 'SELECT physical_uri FROM %1$sshop_url ORDER BY id_shop_url ASC LIMIT 1';
		$sql = sprintf($sql, $this->getShop()->getDatabasePrefix());

		$stm = $h->prepare($sql);
		$stm->execute();
		$old_physical_uri = $stm->fetch(\PDO::FETCH_ASSOC)['physical_uri'];

		$db = $this->getShop()->getMysqlDatabase();

		$sql = 'UPDATE %1$sshop_url SET physical_uri = :new WHERE physical_uri = :old';
		$sql = sprintf($sql, $this->getShop()->getDatabasePrefix());
		$stm = $h->prepare($sql);
		$stm->execute(['new' => $new_physical_uri, 'old' => $old_physical_uri]);

		return $this;
	}
}
