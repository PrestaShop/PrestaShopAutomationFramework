<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use PrestaShop\PSTAF\OnDemand\AccountCreation;
use PrestaShop\PSTAF\EmailReader\GmailReader;
use PrestaShop\PSTAF\FunctionalTest\InvoiceTest;
use PrestaShop\PSTAF\Helper\Spinner;
use PrestaShop\PSTAF\Helper\HumanHash;
use PrestaShop\PSTAF\Exception\FailedTestException;

class EndToEndTest extends \PrestaShop\PSTAF\TestCase\OnDemandTestCase
{
	/**
	 * This is used to make reasonably unique, human readable names.
	 */
	private function _randomAnimalName()
	{
		static $data;

		if (!$data) {
			$data = json_decode(file_get_contents(__DIR__.'/data/animals.json'), true);
		}

		$item = $data[rand(0, count($data) - 1)];
		$item = implode('', array_reverse(explode(',', $item)));
		$m = [];
		$n = preg_match_all('/\w+/', $item, $m);
		return implode('', array_map('ucfirst', $m[0]));
	}

	public function randomAnimalName($maxLength = 15)
	{
		do {
			$animalName = $this->_randomAnimalName();
		} while (strlen($animalName) > $maxLength);

		return $animalName;
	}

	public function newUid()
	{
		$left  = $this->randomAnimalName(15);
		$right = '_'.date("dMYhis").getmypid();

		return $left.$right;
	}

	public function languageAndCountryPairs()
	{
		return [
			['en', 'United States'],
			['fr', 'France'],
			['es', 'Spain'],
			['it', 'Italy'],
			['nl', 'Netherlands'],
			['pt', 'Brazil']
		];
	}

	public function extraData($key = null)
	{
		if ($key) {
			return $this->extraData()[self::getValue('language') . ' ' . self::getValue('country')][$key];
		}

		return [
			'en United States' => [
				'AdminLocalizationExpectedLanguage' => 'English (English)',
				'AdminLocalizationExpectedCountry' 	=> 'United States',
				'addressData' => [
					'countryId' => 21, 			// United States
					'stateId'	=> 11,			// Hawaii
					'postCode' 	=> '12345'
				]
			],
			'fr France' => [
				'AdminLocalizationExpectedLanguage' => 'Français (French)',
				'AdminLocalizationExpectedCountry' 	=> 'France',
				'addressData' => [
					'countryId' => 8, 			// France
					'postCode' 	=> '92300'
				]
			],
			'es Spain' => [
				'AdminLocalizationExpectedLanguage' => 'Español (Spanish)',
				'AdminLocalizationExpectedCountry' 	=> 'Spain',
				'addressData' => [
					'countryId' => 6, 			// Spain
					'stateId'	=> 322,			// Barcelona,
					'dni'		=> '12345678',
					'postCode' 	=> '12345'
				]
			],
			'it Italy' => [
				'AdminLocalizationExpectedLanguage' => 'Italiano (Italian)',
				'AdminLocalizationExpectedCountry' 	=> 'Italy',
				'addressData' => [
					'countryId' => 10, 			// Italy
					'stateId'	=> 135,			// Bergamo
					'postCode' 	=> '33133'
				]
			],
			'nl Netherlands' => [
				'AdminLocalizationExpectedLanguage' => 'Nederlands (Dutch)',
				'AdminLocalizationExpectedCountry' 	=> 'Netherlands',
				'addressData' => [
					'countryId' => 13, 			// Netherlands
					'postCode' 	=> '1234 AB'
				]
			],
			'pt Brazil' => [
				'AdminLocalizationExpectedLanguage' => 'Português BR (Portuguese)',
				'AdminLocalizationExpectedCountry' 	=> 'Brazil',
				'addressData' => [
					'countryId' => 58, 			// Brazil
					'postCode' 	=> '12345-123'
				]
			]
		];
	}

	public function getLanguageAndCountryIdentifier($language, $country)
	{
		$countryParts = preg_split('/\s+/', $country);
		if (count($countryParts) > 1) {
			$c = implode('', array_map(function ($part) {
				return strtoupper($part[0]);
			}, $countryParts));
		} else {
			$c = ucfirst(strtolower(substr($country, 0, 2)));
		}
		$l = substr(strtolower($language), 0, 2);
		return $l.$c;
	}

	public function getEmailReader()
	{
		$reader = new GmailReader(
			$this->getSecrets()['customer']['email'],
			$this->getSecrets()['customer']['gmail_password']
		);

		return $reader;
	}

	/**
	 * @maxattempts 1
	 * @dataProvider languageAndCountryPairs
	 * @parallelize
	 */
	public function testFullProcess($language, $country)
	{
		self::setValue('language', $language);
		self::setValue('country', $country);

		$this->browser->clearCookies();
		$this->homePage->getBrowser()->clearCookies();
		$accountCreation = new AccountCreation($this->homePage);

		$secrets = $this->getSecrets();

		$uid = $this->newUid().'_'.$this->getLanguageAndCountryIdentifier($language, $country);

		$uid = HumanHash::humanMd5($uid);

		self::setValue('uid', $uid);

		$email =  implode("+$uid@", explode('@', $secrets['customer']['email']));
		$shop_name = $uid;

		$data = $accountCreation->createAccountAndShop([
			'email' 	=> $email,
			'password'	=> $secrets['customer']['password'],
			'shop_name' => $shop_name,
			'country'	=> $country,
			'language'	=> $language
		]);

		$this->shop = $data['shop'];
		self::setShop($this->shop);

		$this->shop->getBackOfficeNavigator()->login();

		$loc = $this->shop->getPageObject('AdminLocalization')->visit();

		$actualLanguage = $loc->getDefaultLanguageName();
		$actualCountry = $loc->getDefaultCountryName();

		// Check that the shop is setup with the same country as defined during onboarding
		$expectedCountry = $this->extraData('AdminLocalizationExpectedCountry');
		$this->assertEquals(
			$expectedCountry,
			$actualCountry,
			"Shop doesn't have the expected default country, expected `$expectedCountry` but got `$actualCountry`."
		);

		$expectedLanguage = $this->extraData('AdminLocalizationExpectedLanguage');
		$this->assertEquals(
			$expectedLanguage,
			$actualLanguage,
			"Shop doesn't have the expected default language, expected `$expectedLanguage` but got `$actualLanguage`."
		);

		$this->customersCanRegister();
		$this->basicSellingFeatures();
		$this->emailsAreSent();
	}

	public function getEmailTestAddress()
	{
		$uid = self::getValue('uid');
		return implode("+{$uid}_emailTest@", explode('@', $this->getSecrets()['customer']['email']));
	}

	public function getRegistrationAddress()
	{
		$uid = self::getValue('uid');
		return implode("+{$uid}_registration@", explode('@', $this->getSecrets()['customer']['email']));
	}

	public function customersCanRegister()
	{
		$this->browser->clearCookies();
		
		$registrationAddress =  $this->getRegistrationAddress();
		$this->shop->getRegistrationManager()->registerCustomer([
			'customer_email' => $registrationAddress,
			'customer_password' => '123456789'
		]);

		$addressData = $this->extraData('addressData');

		// Create an address
		$addressForm = $this->shop
							->getPageObject('MyAccount')
							->goToMyAddresses()
							->goToNewAddress();

		$addressForm->setFirstName('Carrie')
					->setLastName('Murray')
					->setAddress('5, main street')
					->setCity('Neverland')
					->setCountryId($addressData['countryId']);

		if (isset($addressData['stateId'])) {
			sleep(5);
			$addressForm->setStateId($addressData['stateId']);
		}

		if (isset($addressData['dni'])) {
			$addressForm->setDni($addressData['dni']);
		}

		$addressForm->setPostCode($addressData['postCode'])
					->setPhone('12345655')
					->setAlias('My Cool Selenium Address');
		
		$addressForm->save();

		$this->shop->getOptionProvider()->setDefaultValues([
			'FrontOfficeLogin' => [
				'customer_email' => $registrationAddress,
				'customer_password' => '123456789'
			]
		]);
	}
	
	public function emailsAreSent()
	{
		$this->browser->clearCookies();
		$this->shop->getBackOfficeNavigator()->login();
		$emails = $this->shop->getPageObject('AdminEmails')->visit();

		$emailTestAddress = $this->getEmailTestAddress();

		$spinner = new Spinner('Could not send an email.', 300, 5000);

		$spinner->assertNoException(function () use ($emails, $emailTestAddress) {
			$emails->sendTestEmailTo($emailTestAddress);
		});

		$this->getEmailReader()->ensureAnEmailIsSentTo($emailTestAddress);
	}

	
	public function basicSellingFeatures()
	{
		$this->browser->clearCookies();

		$scenario = $this->getJSONExample('invoice/simple-order.json');
        $output = InvoiceTest::runScenario($this->shop, $scenario);
        InvoiceTest::checkInvoiceJson($scenario['expect']['invoice'], $output['json']);

        $reference = $output['json']['order']['reference'];

        try {
        	$this->getEmailReader()->ensureAnEmailIsSentTo(
        		$this->getRegistrationAddress(),
        		300,
        		['body' => ['contains' => $reference]]
        	);
        } catch (\Exception $e) {
        	throw new FailedTestException(
        		"No valid order confirmation email received by customer in the 5 minutes after placing an order."
        	);
        }
	}
}