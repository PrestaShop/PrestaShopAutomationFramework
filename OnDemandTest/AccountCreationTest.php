<?php

namespace PrestaShop\PSTAF\OnDemandTest;

use PrestaShop\PSTAF\OnDemand\AccountCreation;
use PrestaShop\PSTAF\EmailReader\GmailReader;

class AccountCreationTest extends \PrestaShop\PSTAF\TestCase\OnDemandTestCase
{
	/**
	 * This is used to make reasonably unique, human readable names.
	 */
	public function randomAnimalName()
	{
		$data = json_decode(file_get_contents(__DIR__.'/data/animals.json'), true);
		$item = $data[rand(0, count($data) - 1)];
		$item = implode('', array_reverse(explode(',', $item)));
		$m = [];
		$n = preg_match_all('/\w+/', $item, $m);
		return implode('', array_map('ucfirst', $m[0]));
	}

	public function newUid()
	{
		$left  = $this->randomAnimalName();
		$right = '_'.date("dMYhis").getmypid();

		if (strlen($left) + strlen($right) > 64) {
			$left = substr($left, 0, 64 - strlen($right));
		}

		return $left.$right;
	}

	public function languageAndCountryPairs()
	{
		return [
			['en', 'United States'	, ['language' => 'English (English)']],
			['fr', 'France'			, ['language' => 'Français (French)']],
			['es', 'Spain'			, ['language' => 'Español (Spanish)']],
			['it', 'Italy'			, ['language' => 'Italiano (Italian)']],
			['nl', 'Netherlands'	, ['language' => 'Nederlands (Dutch)']],
			['pt', 'Brazil'			, ['language' => 'Português BR (Portuguese)']]
		];
	}

	/**
	 * @maxattempts 1
	 * @dataProvider languageAndCountryPairs
	 * @parallelize
	 */
	public function testCreateAccount($language, $country, array $expect)
	{
		$this->browser->clearCookies();
		$this->homePage->getBrowser()->clearCookies();
		$accountCreation = new AccountCreation($this->homePage);

		$secrets = $this->getSecrets();

		$uid = $this->newUid().'_'.$language.preg_replace('/\s+/', '', $country);

		$email =  implode("+$uid@", explode('@', $secrets['customer']['email']));
		$shop_name = $uid;

		$data = $accountCreation->createAccountAndShop([
			'email' 	=> $email,
			'password'	=> $secrets['customer']['password'],
			'shop_name' => $shop_name,
			'country'	=> $country,
			'language'	=> $language
		]);

		$shop = $data['shop'];

		$shop->getBackOfficeNavigator()->login();

		$loc = $shop->getPageObject('AdminLocalization')->visit();

		$actualLanguage = $loc->getDefaultLanguageName();
		$actualCountry = $loc->getDefaultCountryName();

		// Check that the shop is setup with the same country as defined during onboarding
		$this->assertEquals(
			$country,
			$actualCountry,
			"Shop doesn't have the expected default country, expected `$country` but got `$actualCountry`."
		);

		$this->assertEquals(
			$expect['language'],
			$actualLanguage,
			"Shop doesn't have the expected default language, expected `{$expect['language']}` but got `$actualLanguage`."
		);

		$address =  implode("+{$uid}_emailTest@", explode('@', $secrets['customer']['email']));
		$emails = $shop->getPageObject('AdminEmails')->visit();
		$emails->sendTestEmailTo($address);
		
		/*$reader = new GmailReader(
			$this->getSecrets()['customer']['email'],
			$this->getSecrets()['customer']['gmail_password']
		);

		$reader->readEmails()*/
		// $this->browser->waitForUserInput();
	}
}