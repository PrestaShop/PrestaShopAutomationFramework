<?php

namespace PrestaShop;

class Browser
{
	private $driver;
	private $quitted = false;

	public function __construct($seleniumPort)
	{
		$host = 'http://localhost:'.(int)$seleniumPort.'/wd/hub';

		/*
		$profile = new \FirefoxProfile();
		$profile->setPreference('network.http.phishy-userpass-length', 255);
		$profile->setPreference('network.automatic-ntlm-auth.trusted-uris', 'http://v3.prestashop.com,https://v3.prestashop.com');
		echo $profile->encode();
		'firefox_profile' => $profile->encode()
		*/

		$this->driver = \RemoteWebDriver::create($host, [
			'browserName' => 'firefox'
		]);
	}

	public function __destruct()
	{
		if (!$this->quitted)
			$this->quit();
	}

	public function quit()
	{
		$this->quitted = true;
		$this->driver->quit();
	}

	/**
	* Wait for user input - useful when debugging
	*/
	public function waitForUserInput()
    {
		echo "\n\n<<-- [[PAUSED]] hit RETURN to keep going -->>\n\n";
        if(trim(fgets(fopen("php://stdin","r"))) != chr(13)) return $this;
		return $this;
    }

	/**
	* Visit a URL
	*/
	public function visit($url, $basic_auth = null)
	{
		if ($basic_auth)
			$url = preg_replace('/^(\w+:\/\/)/', '\1'.$basic_auth['user'].':'.$basic_auth['pass'].'@', $url);
		$this->driver->get($url);
		return $this;
	}

	public function getAttribute($selector, $attribute)
	{
		return $this->find($selector)->getAttribute($attribute);
	}

	public function getValue($selector)
	{
		return $this->getAttribute($selector, 'value');
	}

	/**
	* Find element(s)
	*/
	public function find($selector, $options = [])
	{
		$unique = !isset($options['unique']) || $options['unique'];
		$tos = 'cssSelector';
		$method = $unique ? 'findElement' : 'findElements';
		$e =  $this->driver->$method(\WebDriverBy::$tos($selector));
		return $e;
	}

	public function all($selector)
	{
		return $this->find($selector, ['unique' => false]);
	}

	public function count($selector)
	{
		return count($this->find($selector, ['unique' => false]));
	}

	public function ensureElementIsOnPage($selector, $exception=null)
	{
		try {
			$this->find($selector);
		} catch (\Exception $e) {
			if ($exception)
				throw $exception;
			else
				throw $e;
		}

		return $this;
	}

	public function click($selector)
	{
		$element = $this->find($selector);
		$element->click();
		return $this;
	}

	public function clickButtonNamed($name)
	{
		$this->click("button[name=$name]");
		return $this;
	}

	public function clickLabelFor($for)
	{
		$element = $this->find("label[for=$for]");
		$element->click();
		return $this;
	}

	public function fillIn($selector, $value)
	{
		$element = $this->find($selector);
		$element->click();
		$element->clear();
		$this->driver->getKeyboard()->sendKeys($value);
		return $this;
	}

	/**
	* Select by value in a select.
	*/
	public function select($selector, $value, &$name = null)
	{
		if (!$value)
			return $this;

		$select = $this->find($selector);
		$name = $select->findElement(\WebDriverBy::cssSelector('option[value="'.$value.'"]'))->getText();

		$select = new \WebDriverSelect($select);
		$select->selectByValue($value);
		return $this;
	}

	/**
	* Select by value in a JQuery chosen select.
	*/
	public function jqcSelect($selector, $value)
	{
		if (!$value)
			return $this;

		$select = $this->find($selector);
		$chosen = $select->findElement(\WebDriverBy::xpath("./following-sibling::div[contains(concat(' ',normalize-space(@class),' '),' chosen-container ')]"));
		$chosen->click();

		$optionElements = $select->findElements(\WebDriverBy::tagName('option'));

		foreach ($optionElements as $n => $element)
		{
			$v = $element->getAttribute('value');
			if ($v == $value)
			{
				$chosen->findElement(\WebDriverBy::cssSelector('*[data-option-array-index="'.$n.'"]'))->click();
				return $this;
			}
		}

		throw new \PrestaShop\Exception\SelectValueNotFoundException();
	}

	/**
	* Check or uncheck a checkbox
	*/
	public function checkbox($selector, $on_off)
	{
		$cb = $this->find($selector);
		if (($on_off && !$cb->isSelected()) || (!$on_off && $cb->isSelected()))
			$cb->click();
		return $this;
	}

	/**
	* Check or uncheck a PrestaShop classical switch
	*/
	public function prestaShopSwitch($idWithoutHash, $yesno)
	{
		$idWithoutHash = $idWithoutHash . ($yesno ? '_on' : '_off');
		$this->click('label[for='.$idWithoutHash.']');
		return $this;
	}

	public function prestaShopSwitchValue($idWithoutHash)
	{
		return $this->find('#'.$idWithoutHash.'_on')->isSelected();
	}

	public function ensureStandardSuccessMessageDisplayed()
	{
		$this->find('div.alert.alert-success');
		return $this;
	}

	/**
	* Wait for element to appear
	*/
	public function waitFor($selector, $timeout_in_second = null, $interval_in_millisecond = null)
	{
		$wait = new \WebDriverWait($this->driver, $timeout_in_second, $interval_in_millisecond);
		$wait->until(function($driver) use ($selector) {
			try {
				$e = $this->find($selector);
				return $e->isDisplayed();
			} catch (\Exception $e) {
				return false;
			}
			return true;
		});
		return $this;
	}

	public function ensureElementShowsUpOnPage($selector, $timeout_in_second = null, $interval_in_millisecond = null)
	{
		$this->waitFor($selector, $timeout_in_second, $interval_in_millisecond);

		return $this;
	}

	/**
	* Return the current URL
	*/
	public function getCurrentURL()
	{
		return $this->driver->getCurrentURL();
	}

	/**
	* Return a parameter from the current URL
	*/
	public function getURLParameter($param)
	{
		$url = $this->getCurrentURL();
		return \PrestaShop\Helper\URL::getParameter($url, $param);
	}

	public function refresh()
	{
		$this->driver->navigate()->refresh();
		return $this;
	}

	public function sleep($seconds)
	{
		sleep($seconds);
		return $this;
	}
}
