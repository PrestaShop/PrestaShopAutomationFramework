<?php

namespace PrestaShop\PSTAF\OnDemand;

use PrestaShop\PSTAF\Exception\InvalidParameterException;
use PrestaShop\PSTAF\Exception\FailedTestException;

class HomePage extends OnDemandPage
{
	private $url;

	public static $twoLetterLanguageCodes = [
		'en' => 'English',
		'fr' => 'Français',
		'es' => 'Español',
		'it' => 'Italiano',
		'pt' => 'Portuguese',
		'nl' => 'Dutch'
	];

	public function getURL()
	{
		if (!$this->url) {
			$this->url = $this->getSecrets()["homePageURL"];
		}

		return $this->url;
	}

	public function getNewStoreURL()
	{
		return rtrim($this->getURL(), '/') . '/en/create-your-online-store';
	}

	public function visit($url = null)
	{
		if (!$url) {
			$url = $this->getURL();
		}

		$this->url = $url;


		$match = [];
    	preg_match('#(\w+://[^/]+)#', $url, $match);
    	$hostPart = $match[1];

		if ($hostPart === 'https://www.prestashop.com') {
			$this->getBrowser()->visit($url);
		} else {
			$this->getBrowser()->visit($url, $this->getSecrets()["htaccess"][$hostPart]);
		}

		return $this;
	}

	public function setLanguage($twoLetterCode)
	{
		if (empty(static::$twoLetterLanguageCodes[$twoLetterCode])) {
			throw new InvalidParameterException("Invalid language code: $twoLetterCode.");
		}


		$wantedLanguage = mb_strtolower(trim(static::$twoLetterLanguageCodes[$twoLetterCode]), 'UTF-8');
		$currentLanguage = mb_strtolower(trim($this->getBrowser()->getText('#menu-language')), 'UTF-8');

		// Can't set the language to the current one, so return if we're already OK.
		if ($currentLanguage === $wantedLanguage) {
			return $this;
		}

		$this->getBrowser()
		->click('#menu-language a.dropdown-toggle')
		->click('#menu-language [title="'.$twoLetterCode.'"]');

		$this->getBrowser()->waitFor('#menu-language');

		$currentLanguage = mb_strtolower(trim($this->getBrowser()->getText('#menu-language')), 'UTF-8');
		if ($currentLanguage !== $wantedLanguage) {
			throw new FailedTestException("Language did not change, got `$currentLanguage` instead of `$wantedLanguage`!");
		}

		return $this;
	}

	public function submitShopCreationBannerForm($shop_name, $email)
	{
		$browser = $this->getBrowser();

		// Depending on the environment, there may be a button to click
		if ($browser->hasVisible('div.store a.btn.get-me-started')) {
			$browser->click('div.store a.btn.get-me-started');
		}

		$browser->fillIn('#create-online-store-shop_name', $shop_name)
		->fillIn('#create-online-store-email', $email)
		->clickFirst('a.submit.btn.get-me-started')
		;

		return new StoreConfigurationPage($this->getBrowser(), $this->getSecrets());
	}

	public function login($email, $password)
	{
		$this->getBrowser()
		->click('#signin_menu > a')
		->fillIn('#header_ips_username', $email)
		->fillIn('#header_ips_password', $password)
		->click('#signin_menu button');

		return new MyStoresPage($this->getBrowser(), $this->getSecrets());
	}

	public function gotoMyStores()
	{
		$this->getBrowser()->click('.store.have-store a.get-me-started');
		return new MyStoresPage($this);
	}
}
