<?php

namespace PrestaShop\PSTAF\TestCase;

use PrestaShop\PSTAF\Shop;
use PrestaShop\PSTAF\SeleniumManager;
use PrestaShop\PSTAF\OnDemand\HomePage;
use PrestaShop\PSTAF\EmailReader\GmailReader;
use PrestaShop\PSTAF\Helper\FileSystem as FS;

class OnDemandTestCase extends TestCase
{
	protected $homePage;

    public function getSecretsName()
    {
        $class = explode('\\', get_called_class());
        return end($class);
    }

	public function getSecrets()
	{
		$path = FS::join($this->getTestPath(), $this->getSecretsName().'.secrets.json');

		if (file_exists($path)) {
			return json_decode(file_get_contents($path), true);
		} else {
			return [];
		}
	}

    public function setUp()
    {
        $this->shop = self::getShop();
        $this->browser = self::getBrowser();
        
        if (!$this->homePage) {
        	$this->homePage = new HomePage($this->browser, $this->getSecrets());
        }
    }

    public function tearDown()
    {
        // Do nothing
    }

    public static function setUpBeforeClass()
    {
        SeleniumManager::ensureSeleniumIsRunning();
        self::getBrowser()->clearCookies();
    }

    public static function tearDownAfterClass()
    {
    }

    public function getEmailReader()
    {
        $reader = new GmailReader(
            $this->getSecrets()['customer']['email'],
            $this->getSecrets()['customer']['gmail_password']
        );

        return $reader;
    }
}
