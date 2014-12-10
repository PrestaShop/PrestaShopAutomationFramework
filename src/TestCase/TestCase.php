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

    public static function setValue($key, $value)
    {
        return self::set('USERDATA_'.$key, $value);
    }

    public static function getValue($key, $defaultValue = null)
    {
        return self::get('USERDATA_'.$key);
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
        $shopManagerOptions = [
            'initial_state' => static::initialState(),
            'temporary' => true,
            'use_cache' => static::$cache_initial_state,
            'browser' => self::getBrowser()
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
        }

        unset(self::$staticData[get_called_class()]);
    }

    public static function getShop()
    {
        return self::get('shop');
    }

    public static function setShop($shop)
    {
        self::set('shop', $shop);
    }

    public static function getBrowser()
    {
        if (!self::get('browser')) {
            $browser = new Browser([
                'host' => SeleniumManager::getHost()
            ]);
            self::set('browser', $browser);
        }

        return self::get('browser');
    }

    public static function getShopManager()
    {
        return self::get('shopManager');
    }

    public function setUp()
    {
        self::set('testNumber', self::get('testNumber', 0) + 1);

        if (self::get('testNumber') > 1) {
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

    public function getTestPath()
    {
        $group = [];
        if (preg_match('#PrestaShop\\\PSTAF\\\(\w+)\\\#', get_called_class(), $group)) {
            $group = $group[1];
        } else {
            return false;
        }

        return realpath(__DIR__.'/../../'.$group.'/');
    }

    public function getExamplesPath()
    {
        $class = explode('\\', get_called_class());
        $class = end($class);
        
        $testPath = $this->getTestPath();

        if ($testPath) {
            $path = FS::join($testPath, $class, 'examples');
        } else {
            $path = null;
        }

        if (!$path) {
            throw new \PrestaShop\PSTAF\Exception\FailedTestException("No example files found for $class.\nThey should have been in tests-available/$class/examples/.");
        }

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

    /**
     * Base directory where a test may write stuff.
     */
    public function getOutputDir()
    {
        $folder = str_replace('\\', '/', get_called_class());
        $folder = preg_replace('#^PrestaShop/PSTAF/#', '', $folder);

        $dir = FS::join('test-results', $folder);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return $dir;
    }

    /**
     * Depending on the test runner, a more specific output dir
     * may be available, this function retrieves it.
     */
    public function getArtefactsDir()
    {
        $dir = $this->getOutputDir();

        if ($this->shortName) {
            $dir = FS::join($dir, $this->shortName);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }        

        return $dir;
    }

    public function writeArtefact($name, $contents)
    {
        file_put_contents(FS::join($this->getArtefactsDir(), $name), $contents);

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

        $dir = $this->getArtefactsDir();
        
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
            static::getBrowser()->recordScreenshots($screenshotsDir);
        }
    }

    public function getShortName()
    {
        return $this->shortName;
    }
}
