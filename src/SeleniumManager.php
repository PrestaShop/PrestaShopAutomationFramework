<?php

namespace PrestaShop\PSTAF;

use \PrestaShop\PSTAF\Helper\FileSystem as FS;

class SeleniumManager
{
    public static function findOpenPort($startInclusive = 4444, $endInclusive = 8888)
    {
        for ($p = $startInclusive; $p <= $endInclusive; $p++) {
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

        if (!$path) {
            $base = realpath(__DIR__.'/../');
            foreach (scandir($base) as $entry) {
                if (preg_match('/selenium-server-standalone(?:-\d+(?:\.\d+)*)?\.jar$/', $entry)) {
                    $path = FS::join($base, $entry);
                    break;
                }
            }
        }

        return $path;
    }

    public static function start($directory = null)
    {
        $sjp = static::getSeleniumJARPath();

        $port = static::findOpenPort();

        $process = new \djfm\Process\Process('java', [], [
            '-jar' => $sjp,
            '-port' => $port
        ]);

        $upid = $process->run(STDIN, 'selenium.log', 'selenium.log');

        $pidFile = 'selenium.pid';
        if ($directory) {
            $pidFile = FS::join($directory, $pidFile);
        }

        file_put_contents($pidFile, json_encode([
            'pid' => $upid,
            'port' => $port,
            'host' => 'http://127.0.0.1:'.$port.'/wd/hub'
        ]));
    }

    public static function stop($walkDirectoryTreeUp = false)
    {
        if (($data = static::started($walkDirectoryTreeUp))) {
            \djfm\Process\Process::killByUPID($data['pid']);
            unlink($data['pidFile']);
        }
    }

    public static function startedInHigherDirectory()
    {
        return static::started(true);
    }

    public static function startedInCWD()
    {
        return static::started(false);
    }

    public static function started($walkDirectoryTreeUp = false)
    {
        $base = realpath('.');
        $filename = 'selenium.pid';

        for (;;) {
            $candidate = FS::join($base, $filename);
            if (file_exists($candidate)) {

                 $data = json_decode(file_get_contents($candidate), true);
                 $data['pidDirectory'] = $base;
                 $data['pidFile'] = $candidate;

                 if (\djfm\Process\Process::runningByUPID($data['pid'])) {
                    return $data;
                 } else {
                    unlink($candidate);
                 }

            } elseif (dirname($base) !== $base) {
                // This condition checks we're not at the filesystem root
                $base = dirname($base);
            } else {
                break;
            }

            if (!$walkDirectoryTreeUp) {
                break;
            }
        }

        return false;
    }

    public static function isSeleniumStarted()
    {
        if (getenv('SELENIUM_HOST')) {
            return true;
        }

       return static::started(true) ? true : false;
    }

    public static function getHost()
    {
        if (($sh = getenv('SELENIUM_HOST'))) {
            return $sh;
        }

        return static::started(true)['host'];
    }

    public static function ensureSeleniumIsRunning()
    {
        if (!static::started(true) && !getenv('SELENIUM_HOST')) {
            throw new \PrestaShop\PSTAF\Exception\SeleniumIsNotRunningException();
        }
    }
}
