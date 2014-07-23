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

	public function databaseExists($database_name = null)
	{
		if ($database_name === null)
			$database_name = $this->getShop()->getMysqlDatabase();

		$h = $this->getPDO();
		if (!$h)
			return false;

		$sql = 'SHOW DATABASES LIKE \''.$database_name.'\'';
		$res = $h->exec($sql);
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
		$command = $command
		.' -u'.escapeshellcmd($this->getShop()->getMysqlUser());
		
		if ($this->getShop()->getMysqlPass())
			$command .= ' -p'.escapeshellcmd($this->getShop()->getMysqlPass());

		$command = $command
		.' -h'.escapeshellcmd($this->getShop()->getMysqlHost())
		.' -P'.escapeshellcmd($this->getShop()->getMysqlPort())
		.implode('', array_map(function($arg){
			if (is_array($arg))
				return ' '.$arg[0];
			else
				return ' '.escapeshellcmd($arg);
		}, $arguments))
		.' 2>/dev/null'; // quickfix for warning about using password on command line

		return $command;
	}

	public function duplicateDatabaseTo($new_database_name)
	{
		$old_database_name = $this->getShop()->getMysqlDatabase();

		$commands = [
			$this->buildMysqlCommand('mysqladmin', ['create', $new_database_name]),
			$this->buildMysqlCommand('mysqldump', [$old_database_name])
			.' | '.$this->buildMysqlCommand('mysql', [$new_database_name])
		];	

		foreach ($commands as $command)
		{
			exec($command);
		}
	}

	public function dumpTo($path)
	{
		$command = $this->buildMysqlCommand('mysqldump', [$this->getShop()->getMysqlDatabase(), ['>'], $path]);
		exec($command);
	}

	public function loadDump($dump_path, $database_name = null)
	{
		if (!$database_name)
			$database_name = $this->getShop()->getMysqlDatabase();

		if (!$this->databaseExists($database_name))
		{
			$command = $this->buildMysqlCommand('mysqladmin', ['create', $database_name]);
			exec($command);
		}

		$command = $this->buildMysqlCommand('mysql', [$database_name, ['<'], $dump_path]);
		exec($command);

		return $this;
	}

	public function changeShopUrlPhysicalURI($old_physical_uri, $new_physical_uri)
	{
		$h = $this->getPDO();
		$sql = 'UPDATE %1$sshop_url SET physical_uri = :new WHERE physical_uri = :old';
		$sql = sprintf($sql, $this->getShop()->getDatabasePrefix());
		$stm = $h->prepare($sql);
		$stm->execute(['new' => $new_physical_uri, 'old' => $old_physical_uri]);
	}
}
