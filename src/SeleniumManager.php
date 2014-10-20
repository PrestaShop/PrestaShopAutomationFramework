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

	public static function getPIDFile()
	{
		$base = realpath('.');
		$filename = 'selenium.pid';

		for(;;) {
			$candidate = \PrestaShop\Helper\FileSystem::join($base, $filename);
			if (file_exists($candidate)) {
				return $candidate;
			} elseif (dirname($base) !== $base) {
				// This condition checks we're not at the filesystem root
				$base = dirname($base);
			} else {
				throw new \PrestaShop\Exception\SeleniumPIDFileNotFoundException();
			}
		}
	}

	public static function isSeleniumStarted()
	{
		if (getenv('SELENIUM_HOST')) {
			return true;
		}

		try {
			$pid = json_decode(file_get_contents(static::getPIDFile()), true)['pid'];
			return \djfm\Process\Process::runningByUPID($pid) ? $pid : false;
		} catch (\PrestaShop\Exception\SeleniumPIDFileNotFoundException $e) {
			return false;
		}
		
	}

	public static function getMyPort()
	{
		return (int)json_decode(file_get_contents(static::getPIDFile()), true)['port'];
	}

	public static function getHost()
	{
		if (($sh = getenv('SELENIUM_HOST'))) {
			return $sh;
		}

		return 'http://127.0.0.1:'.SeleniumManager::getMyPort().'/wd/hub';
	}

	public static function startSelenium()
	{
		if (static::isSeleniumStarted() !== false)
			return;

		$sjp = static::getSeleniumJARPath();

		$port = static::findOpenPort();

		$process = new \djfm\Process\Process('java', [], [
			'-jar' => $sjp,
			'-port' => $port
		]);

		$upid = $process->run(STDIN, 'selenium.log', 'selenium.log');

		file_put_contents('selenium.pid', json_encode(['pid' => $upid, 'port' => $port]));
	}

	public static function stopSelenium()
	{
		$pid = static::isSeleniumStarted();
		if (false !== $pid)
		{
			\djfm\Process\Process::killByUPID($pid);
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
