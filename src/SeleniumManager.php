<?php

namespace PrestaShop;

class SeleniumManager
{
	public static function findOpenPort($startInclusive = 4444, $endInclusive = 8888)
	{
		for ($p = $startInclusive; $p <= $endInclusive; $p++)
		{
			$conn = @fsockopen('localhost', $p);
			if (is_resource($conn))
				fclose($conn);
			else
				return $p;
		}
		return false;
	}

	public static function getSeleniumJARPath()
	{
		static $path = null;

		if (!$path)
		{
			$base = realpath(__DIR__.'/../');
			foreach (scandir($base) as $entry)
			{
				if (preg_match('/selenium-server-standalone(?:-\d+(?:\.\d+)*)?\.jar$/', $entry))
				{
					$path = \PrestaShop\Helper\FileSystem::join($base, $entry);
					break;
				}
			}
		}

		return $path;
	}

	public static function isMySeleniumPID($pid)
	{
		// TODO: Will not work on windows
		$cmd = 'ps '.escapeshellarg($pid);
		$info = `$cmd`;
		return (strpos($info, static::getSeleniumJARPath()) !== false);
	}

	public static function isSeleniumStarted()
	{
		// TODO: Will not work on windows
		if (file_exists('selenium.pid'))
		{
			$pid = (int)json_decode(file_get_contents('selenium.pid'), true)['pid'];
			return static::isMySeleniumPID($pid) ? $pid : false;
		}
		else
			return false;
	}

	public static function getMyPort()
	{
		return (int)json_decode(file_get_contents('selenium.pid'), true)['port'];
	}

	public static function startSelenium()
	{
		// TODO Windows
		if (static::isSeleniumStarted() !== false)
			return;

		$sjp = static::getSeleniumJARPath();

		$port = static::findOpenPort();

		$cmd = 'exec java -jar '.escapeshellcmd($sjp).' -port '.escapeshellcmd($port);

		$io = [
			0 => ['file', 'selenium.log', 'a'],
			1 => ['file', '/dev/null', 'r'],
			2 => ['file', 'selenium.log', 'a']
		];

		$pipes = [];

		$res = proc_open($cmd, $io, $pipes);
		if (is_resource($res))
		{
			$pid = proc_get_status($res)['pid'];
			file_put_contents('selenium.pid', json_encode(['pid' => $pid, 'port' => $port]));
		}
	}

	public static function stopSelenium()
	{
		// TODO Windows
		$pid = static::isSeleniumStarted();
		if (false !== $pid)
		{
			posix_kill($pid, 15);
			unlink('selenium.pid');
		}
	}

	public static function ensureSeleniumIsRunning()
	{
		if (static::isSeleniumStarted() === false)
		{
			throw new \PrestaShop\Exception\SeleniumIsNotRunningException();
		}
	}
}
