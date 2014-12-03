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

    public function extraLocalizationData($key = null, $default = null)
    {
        if ($key) {
            $localKey = self::getValue('language') . ' ' . self::getValue('country');
            $path = explode('.', $key);
            array_unshift($path, $localKey);

            $data = $this->extraLocalizationData();
            for (;;) {
                $level = array_shift($path);
                if (isset($data[$level])) {
                    $data = $data[$level];
                    if (empty($path)) {
                        return $data;
                    }
                } else {
                    return $default;
                }
            }
        }

        return [
            'en United States' => [
                'AdminLocalizationExpectedLanguage' => 'English (English)',
                'AdminLocalizationExpectedCountry'  => 'United States',
                'addressData' => [
                    'countryId' => 21,          // United States
                    'stateId'   => 11,          // Hawaii
                    'postCode'  => '12345'
                ]
            ],
            'fr France' => [
                'AdminLocalizationExpectedLanguage' => 'Français (French)',
                'AdminLocalizationExpectedCountry'  => 'France',
                'addressData' => [
                    'countryId' => 8,           // France
                    'postCode'  => '92300'
                ]
            ],
            'es Spain' => [
                'AdminLocalizationExpectedLanguage' => 'Español (Spanish)',
                'AdminLocalizationExpectedCountry'  => 'Spain',
                'addressData' => [
                    'countryId' => 6,           // Spain
                    'stateId'   => 322,         // Barcelona,
                    'dni'       => '12345678',
                    'postCode'  => '12345'
                ]
            ],
            'it Italy' => [
                'AdminLocalizationExpectedLanguage' => 'Italiano (Italian)',
                'AdminLocalizationExpectedCountry'  => 'Italy',
                'addressData' => [
                    'countryId' => 10,          // Italy
                    'stateId'   => 135,         // Bergamo
                    'postCode'  => '33133'
                ]
            ],
            'nl Netherlands' => [
                'AdminLocalizationExpectedLanguage' => 'Nederlands (Dutch)',
                'AdminLocalizationExpectedCountry'  => 'Netherlands',
                'addressData' => [
                    'countryId' => 13,          // Netherlands
                    'postCode'  => '1234 AB'
                ]
            ],
            'pt Brazil' => [
                'AdminLocalizationExpectedLanguage' => 'Português BR (Portuguese)',
                'AdminLocalizationExpectedCountry'  => 'Brazil',
                'addressData' => [
                    'countryId' => 58,          // Brazil,
                    'stateId'   => 316,         // Amazonas
                    'postCode'  => '12345-123'
                ]
            ]
        ];
    }
}
