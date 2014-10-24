<?php

namespace PrestaShop\TestCase;

use \PrestaShop\ShopManager;
use \PrestaShop\Shop;

abstract class TestCase extends \PHPUnit_Framework_TestCase implements \PrestaShop\Ptest\TestClass\Basic
{
    private static $shops = [];
    private static $shop_managers = [];
    private static $test_numbers = [];
    private static $browsers = [];
    private $shortName;

    protected static $cache_initial_state = true;

    private static function newShop()
    {
        $class = get_called_class();

        if (!isset(self::$browsers[$class])) {
            $browser = new \PrestaShop\PSBrowser([
                'host' => \PrestaShop\SeleniumManager::getHost()
            ]);
            self::$browsers[$class] = $browser;
        }

        self::$shops[$class] = self::getShopManager()->getShop([
            'initial_state' => static::initialState(),
            'temporary' => true,
            'use_cache' => true,
            'browser' => self::$browsers[$class]
        ]);

        self::$shops[$class]->getBrowser()->clearCookies();
    }

    public static function setUpBeforeClass()
    {
        \PrestaShop\SeleniumManager::ensureSeleniumIsRunning();
        $class = get_called_class();
        $manager = ShopManager::getInstance();
        self::$shop_managers[$class] = $manager;
        self::newShop();
        register_shutdown_function([$class, 'tearDownAfterClass']);
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

    public static function beforeAll()
    {

    }

    public static function tearDownAfterClass()
    {
        $class = get_called_class();
        if (isset(self::$shops[$class])) {
            static::getShopManager()->cleanUp(static::getShop());
            unset(self::$shops[$class]);
        }

        if (isset(self::$browsers[$class])) {
            unset(self::$browsers[$class]);
        }
    }

    public static function getShop()
    {
        $class = get_called_class();

        return self::$shops[$class];
    }

    private static function getShopManager()
    {
        $class = get_called_class();

        return self::$shop_managers[$class];
    }

    public function setUp()
    {
        $class = get_called_class();

        if (!isset(self::$test_numbers[$class]))
            self::$test_numbers[$class] = 0;
        else
            self::$test_numbers[$class]++;

        if (self::$test_numbers[$class] > 0) {
            // clean current shop
            static::getShopManager()->cleanUp(static::getShop(), $leaveBrowserRunning = true);
            // get a new one
            self::newShop();
        }

        $this->shop = static::getShop();
    }

    public function tearDown()
    {
        // TODO: restore state of shop
    }

    public function getExamplesPath()
    {
        $class = explode('\\', get_called_class());
        $class = end($class);

        $path = realpath(__DIR__.'/../../tests-available/'.$class.'/examples/');

        if (!$path)
            throw new \PrestaShop\Exception\FailedTestException("No example files found for $class.\nThey should have been in tests-available/$class/examples/.");

        return $path;
    }

    public function getExamplePath($name)
    {
        return \PrestaShop\Helper\FileSystem::join($this->getExamplesPath(), $name);
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
            $dir .= DIRECTORY_SEPARATOR . $this->shortName;
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }        

        $path = $dir.DIRECTORY_SEPARATOR.$name;

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

        $dir = $this->getOutputDir().DIRECTORY_SEPARATOR.$shortName;
        
        $screenshotsDir = $dir.DIRECTORY_SEPARATOR.'screenshots';

        if (is_dir($screenshotsDir)) {
            foreach (scandir($screenshotsDir) as $entry) {
                if ($entry[0] === '.') {
                    continue;
                }
                unlink($screenshotsDir.DIRECTORY_SEPARATOR.$entry);
            }
        } else {
            mkdir($screenshotsDir, 0777, true);
        }

        static::getShop()->getBrowser()->recordScreenshots($screenshotsDir);
    }
}
