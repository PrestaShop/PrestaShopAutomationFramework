<?php

namespace PrestaShop\PSTAF\TestCase;

use PrestaShop\PSTAF\SeleniumManager;
use PrestaShop\PSTAF\ShopManager;
use PrestaShop\PSTAF\Shop;
use PrestaShop\PSTAF\PSBrowser as Browser;
use PrestaShop\PSTAF\Helper\FileSystem as FS;

abstract class TestCase extends \PHPUnit_Framework_TestCase implements \PrestaShop\Ptest\TestClass\Basic
{
    // a static datastore using get_called_class() as keys
    private static $staticData = [];

    private $shortName;

    // Utility function to store static data safely even when called from inheriting class
    private static function get($key, $defaultValue = null)
    {
        $className = get_called_class();

        if (!array_key_exists($className, self::$staticData)) {
            return $defaultValue;
        }

        if (array_key_exists($key, self::$staticData[$className])) {
            return self::$staticData[$className][$key];
        } else {
            return $defaultValue;
        }
    }

    // Utility function to store static data safely even when called from inheriting class
    private static function set($key, $value)
    {
        $className = get_called_class();

        if (!array_key_exists($className, self::$staticData)) {
            self::$staticData[$className] = [];
        }

        if (!array_key_exists($className, self::$staticData)) {
            self::$staticData[$className] = [];
        }

        self::$staticData[$className][$key] = $value;
    }

    // Whether or not to cache the initial shop state
    protected static $cache_initial_state = true;

    protected static function shopManagerOptions()
    {
        return [];
    }

    public static function initialState()
    {
        return [
            'ShopInstallation' => [
                'language' => 'en',
                'country' => 'us'
            ]
        ];
    }

    private static function newShop()
    {
        if (!self::get('browser')) {
            $browser = new Browser([
                'host' => SeleniumManager::getHost()
            ]);
            self::set('browser', $browser);
        }

        $shopManagerOptions = [
            'initial_state' => static::initialState(),
            'temporary' => true,
            'use_cache' => static::$cache_initial_state,
            'browser' => self::get('browser')
        ];

        $shopManagerOptions = array_merge($shopManagerOptions, static::shopManagerOptions());

        $shop = self::getShopManager()->getShop($shopManagerOptions);
        $shop->getBrowser()->clearCookies();

        self::set('shop', $shop);
    }

    public static function setUpBeforeClass()
    {
        SeleniumManager::ensureSeleniumIsRunning();

        self::set('shopManager', ShopManager::getInstance());
        self::newShop();
        register_shutdown_function([get_called_class(), 'tearDownAfterClass']);
    }

    public static function tearDownAfterClass()
    {
        if (($shop = self::getShop())) {
            static::getShopManager()->cleanUp($shop);
            self::set('shop', null);
        }

        if (self::get('browser')) {
            self::set('browser', null);
        }
    }

    public static function getShop()
    {
        return self::get('shop');
    }

    public static function getBrowser()
    {
        return self::get('browser');
    }

    public static function getShopManager()
    {
        return self::get('shopManager');
    }

    public function setUp()
    {
        self::set('testNumber', self::get('testNumber', 0) + 1);

        if (self::get('testNumber') > 0) {
            // clean current shop
            static::getShopManager()->cleanUp(self::getShop(), $leaveBrowserRunning = true);
            // get a new one
            self::newShop();
        }

        $this->shop = static::getShop();
        $this->browser = static::getBrowser();
    }

    public function tearDown()
    {

    }

    public function getExamplesPath()
    {
        $class = explode('\\', get_called_class());
        $class = end($class);

        $path = realpath(__DIR__.'/../../FunctionalTest/'.$class.'/examples/');

        if (!$path)
            throw new \PrestaShop\PSTAF\Exception\FailedTestException("No example files found for $class.\nThey should have been in tests-available/$class/examples/.");

        return $path;
    }

    public function getExamplePath($name)
    {
        return FS::join($this->getExamplesPath(), $name);
    }

    public function getJSONExample($example)
    {
        return json_decode(file_get_contents($this->getExamplePath($example)), true);
    }

    public function jsonExampleFiles()
    {
        return $this->exampleFiles('json');
    }

    public function exampleFiles($ext)
    {
        $files = [];
        $src_dir = $this->getExamplesPath();

        if (!$src_dir)
            return $files;

        foreach (scandir($src_dir) as $entry)
            if (preg_match('/\.'.$ext.'$/', $entry))
                $files[] = [$entry];

        return $files;
    }

    public function getOutputDir()
    {
        $class = explode('\\', get_called_class());
        $class = end($class);

        $dir = 'test-results/'.$class;

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    public function writeArtefact($name, $contents)
    {
        $dir = $this->getOutputDir();

        if ($this->shortName) {
            $dir = FS::join($dir, $this->shortName);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }        

        $path = FS::join($dir, $name);

        file_put_contents($path, $contents);

        return $this;
    }

    public function makeFileNameCompatibleRepresentation($value) {
        
        $clean = function($str) {
            return str_replace(
                ['<', '>', ':', '"', '/', '\\', '|', '?', '*'],
                ['(lt)', '(gt)', '(colon)', "''", '(fs)', '(bs)', '(or)', '(qstnmrk)', '(x)'],
                $str
            );
        };

        if (is_scalar($value)) {
            return $clean((string)$value);
        } elseif (is_object($value)) {
            return '{'.spl_object_hash($value).'}';
        } elseif (is_array($value)) {
            $parts = [];
            foreach ($value as $k => $v) {
                $part = '';
                if (is_string($k)) {
                    $part .= $clean($k).'=';
                }
                $part .= $this->makeFileNameCompatibleRepresentation($v);
                $parts[] = $part;
            }
            return '['.implode(', ', $parts).']';
        }
    }

    public function aboutToStart($methodName, array $arguments = null)
    {
        $shortName = $methodName;
        if (!empty($arguments)) {
            $shortName .= ' - '.implode(' ', array_map([$this, 'makeFileNameCompatibleRepresentation'], $arguments));
        }

        $this->shortName = $shortName;

        $dir = FS::join($this->getOutputDir(), $this->shortName);
        
        $screenshotsDir = FS::join($dir, 'screenshots');

        if (is_dir($screenshotsDir)) {
            foreach (scandir($screenshotsDir) as $entry) {
                if ($entry[0] === '.') {
                    continue;
                }
                unlink(FS::join($screenshotsDir, $entry));
            }
        } else {
            mkdir($screenshotsDir, 0777, true);
        }

        if (!getenv('NO_SCREENSHOTS')) {
            static::getShop()->getBrowser()->recordScreenshots($screenshotsDir);
        }
    }
}
