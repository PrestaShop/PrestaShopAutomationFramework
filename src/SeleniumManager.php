<?php

namespace PrestaShop\PSTAF;

use Exception;

use PrestaShop\PSTAF\Helper\FileSystem as FS;
use djfm\Process\Process;


class SeleniumManager
{
    private static $host;
    private static $processesToKill = array();

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

    private static function buildStartProcess()
    {
        $sjp = static::getSeleniumJARPath();

        $port = static::findOpenPort();

        $process = new \djfm\Process\Process('java', [], [
            '-jar' => $sjp,
            '-port' => $port
        ], [
            'upid' => true
        ]);

        return [$process, $port];
    }

    public static function start($directory = null)
    {
        list($process, $port) = self::buildStartProcess();

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

        return $process;
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
        return self::getHost() ? true : false;
    }

    public static function getHost()
    {
        if (($sh = getenv('SELENIUM_HOST'))) {
            return $sh;
        } else if (self::$host) {
            return self::$host;
        } else if (($started = static::started(true))) {
            return $started['host'];
        }

        return false;
    }

    public static function ensureSeleniumIsRunning()
    {
        if (!self::isSeleniumStarted()) {
            throw new \PrestaShop\PSTAF\Exception\SeleniumIsNotRunningException();
        }
    }

    public static function spawnSelenium($headless = false)
    {
        $display = null;

        if ($headless) {
            for ($displayNumber = 10; $displayNumber < 50; ++$displayNumber) {

                try {
                    $xprocess = new Process('Xvfb', [':' . $displayNumber], ['-ac' => ''], ['upid' => true]);
                    $xprocess->run();
                    sleep(1);
                } catch (Exception $e) {
                    // never mind, try next display...
                }


                if ($xprocess->running()) {
                    self::$processesToKill[] = $xprocess;
                    $display = ':' . $displayNumber;
                    break;
                }
            }
        }

        list($process, $port) = self::buildStartProcess();

        self::$processesToKill[] = $process;

        if ($display) {
            $process->setEnv('DISPLAY', $display);
        }

        $process->run(null, 'selenium.log', 'selenium.log');

        if ($process->running()) {
            self::$host = 'http://127.0.0.1:' . $port . '/wd/hub';
            sleep(5);
        }

        register_shutdown_function(function () {
            self::unSpawnSelenium();
        });
    }

    public static function unSpawnSelenium()
    {
        foreach (self::$processesToKill as $process) {
            $process->kill();
        }

        self::$processesToKill = [];
        self::$host = null;
    }

}
