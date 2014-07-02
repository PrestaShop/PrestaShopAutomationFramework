<?php

namespace PrestaShop;

class Browser
{
	private $driver;
	private $quitted = false;

	public function __construct($seleniumPort)
	{
		$host = 'http://localhost:'.(int)$seleniumPort.'/wd/hub';

		$this->driver = \RemoteWebDriver::create($host, \DesiredCapabilities::firefox());
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
        if(trim(fgets(fopen("php://stdin","r"))) != chr(13)) return;
    }

	/**
	* Visit a URL
	*/
	public function visit($url)
	{
		$this->driver->get($url);
		return $this;
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

	public function ensureElementIsOnPage($selector)
	{
		$this->find($selector);
	}

	public function click($selector)
	{
		$element = $this->find($selector);
		$element->click();
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
	public function select($selector, $value)
	{
		if (!$value)
			return $this;

		$select = new \WebDriverSelect($this->find($selector));
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

	public function sleep($seconds)
	{
		sleep($seconds);
		return $this;
	}
}
