<?php

namespace PrestaShop;

class Browser
{
	private $driver;
	private $quitted = false;

	public function __construct($seleniumSettings)
	{
		$caps = [
			'browserName' => 'firefox',
		];

		$tunnel_identifier = getenv('TRAVIS_JOB_NUMBER');

		if ($tunnel_identifier) {
			$caps['tunnel-identifier'] = $tunnel_identifier;
		}

		$this->driver = \RemoteWebDriver::create($seleniumSettings['host'], $caps);

		$this->driver->manage()->window()->setSize(new \WebDriverDimension(1920, 1200));
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

	public function doThenComeBack(callable $callback)
	{
		$url = $this->getCurrentURL();

		$res = $callback();

		$this->visit($url);

		return $res;
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

	public function getPageSource()
	{
		return $this->driver->getPageSource();
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

		$m = [];
		if (preg_match('/^{xpath}(.*)$/', $selector, $m))
		{
			$selector = $m[1];
			$tos = 'xpath';
		}
		else
			$tos = 'cssSelector';
		
		$method = $unique ? 'findElement' : 'findElements';

		if (isset($options['wait']) && $options['wait'] === false)
			return $this->driver->$method(\WebDriverBy::$tos($selector));

		$spin = new \PrestaShop\Helper\Spinner('Could not find element.', 5);

		return $spin->assertNoException(function() use ($method, $tos, $selector) {
			return $this->driver->$method(\WebDriverBy::$tos($selector));
		});
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

	public function hover($selector)
	{
		$element = $this->find($selector);
		/*
		$coords = $element->getCoordinates();
		$this->driver->getMouse()->mouseMove($coords, 5, 5);*/
		$this->driver->action()->moveToElement($element)->perform();
		return $this;
	}

	public function clickButtonNamed($name)
	{
		$buttons = $this->find("button[name=$name]", ['unique' => false]);
		foreach ($buttons as $button)
		{
			if ($button->isDisplayed() && $button->isEnabled())
			{
				$button->click();
				return $this;
			}
		}
		throw new \Exception("Could not find any visible and enabled button named $name");
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

	public function setFile($selector, $path)
	{
		$element = $this->find($selector);
		if (!$element->isDisplayed())
		{
			$this->executeScript("
				arguments[0].style.setProperty('display', 'inherit', 'important');
				arguments[0].style.setProperty('visibility', 'visible', 'important');
				arguments[0].style.setProperty('width', 'auto', 'important');
				arguments[0].style.setProperty('height', 'auto', 'important');
			", [$element]);
		}
		$element->sendKeys($path);

		return $this;
	}

	public function sendKeys($keys)
	{
		$this->driver->getKeyboard()->sendKeys($keys);
		return $this;
	}

	/**
	* Select by value in a select.
	*/
	public function select($selector, $value)
	{
		if ($value === null)
			return $this;

		$elt = $this->find($selector);

		$select = new \WebDriverSelect($elt);
		$select->selectByValue($value);
		return $this;
	}

	/**
	 * Select by value in a multiple select.
	 * Options is an array of values.
	 */
	public function multiSelect($selector, $options)
	{
		$elem = $this->find($selector);
		$elem->click();
		sleep(1); 	// strangely, this is needed, 
				  	// otherwise Selenium throws a StaleElementException
					// I don't have the faintest idea why

		$option_elts = $elem->findElements(\WebDriverBy::cssSelector('option'));
		
		$this->driver->getKeyboard()->pressKey(\WebDriverKeys::CONTROL);
		
		$matched = 0;

		foreach ($option_elts as $opt)
		{
			if (in_array($opt->getAttribute('value'), $options))
			{
				$matched += 1;
				if (!$opt->isSelected())
				{
					$opt->click();
				}
			}
			elseif ($opt->isSelected()) {
				$opt->click();
			}
		}
		
		$this->driver->getKeyboard()->releaseKey(\WebDriverKeys::CONTROL);

		if ($matched !== count($options))
			throw new \Exception('Could not select all values.');

		return $this;
	}

	/**
	* Get Select Options as associative array
	*/
	public function getSelectOptions($selector)
	{
		$options = [];
		$elem = $this->find($selector);
		$elem->click();
		// Multiselects apparently need us to slow down
		if ($elem->getAttribute('multiple'))
			sleep(1);
		$select = new \WebDriverSelect($elem);
		foreach ($select->getOptions() as $opt)
		{
			$options[$opt->getAttribute('value')] = $opt->getText();
		}
		$this->sendKeys(\WebDriverKeys::ESCAPE);
		return $options;
	}


	/**
	* Select by value in a JQuery chosen select.
	*/
	public function jqcSelect($selector, $value)
	{
		/**
		* Spin this test, because since JQuery is involved, the DOM is likely not to be ready.
		*/
		$spinner = new \PrestaShop\Helper\Spinner(null, 5, 1000);

		$spinner->assertBecomesTrue(function() use ($selector, $value) {
			$this->_jqcSelect($selector, $value);
			return true;
		});

		return $this;
	}

	/**
	* Select by value in a JQuery chosen select,
	* this function is called by jqcSelect and wrapped into a Spin assertion
	* because it is very likely to fail on first attempt.
	*/
	private function _jqcSelect($selector, $value)
	{
		if (!$value)
			return $this;

		$select = $this->find($selector);
		$chosen = $select->findElement(\WebDriverBy::xpath("./following-sibling::div[contains(concat(' ',normalize-space(@class),' '),' chosen-container ')]"));
		$chosen->click();

		$option_containers = [$select];

		$optgroups = $select->findElements(\WebDriverBy::tagName('optgroup'));
		if (count($optgroups) > 0)
		{
			$option_containers = $optgroups;
		}

		$n = 0;

		/**
		* Here we loop over the options inside the original select, recording their positions ($n).
		* When we find the option with the required value, we use the position to click the item in
		* the jQuery chosen box, referencing it by its data-option-array-index attribute.
		* There is a special treatment for optgroups, because optgroups are counted by jQuery Chosen
		* as an option, hence increment the counter.
		*/

		foreach ($option_containers as $option_container)
		{
			if ($option_container->getTagName() === 'optgroup')
			{
				$n += 1;
			}

			$optionElements = $option_container->findElements(\WebDriverBy::tagName('option'));

			foreach ($optionElements as $element)
			{
				$v = $element->getAttribute('value');
				if ($v == $value)
				{
					$chosen->findElement(\WebDriverBy::cssSelector('*[data-option-array-index="'.$n.'"]'))->click();
					return $this;
				}

				$n += 1;
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

	public function ensureStandardSuccessMessageDisplayed($error_explanation = null)
	{
		try {
			$element = $this->find('div.alert.alert-success');
		} catch (\Exception $e)
		{
			throw new \PrestaShop\Exception\StandardSuccessMessageNotDisplayedException($error_explanation);
		}

		if (!$element->isDisplayed())
			throw new \PrestaShop\Exception\StandardSuccessMessageNotDisplayedException($error_explanation);

		return $this;
	}

	public function ensureStandardErrorMessageNotDisplayed($error_explanation = null)
	{
		try {
			$this->find('div.alert.alert-error', ['wait' => false]);
			throw new \PrestaShop\Exception\StandardErrorMessageDisplayedException($error_explanation);
		} catch (\Exception $e)
		{
			// That's expected :)
		}
		
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

	/**
	 * Refresh the page, using same HTTP verb
	 * @return $this
	 */
	public function refresh()
	{
		$this->driver->navigate()->refresh();
		return $this;
	}

	/**
	 * Refresh the page, performing a GET
	 * @return $this
	 */
	public function reload()
	{
		$this->visit($this->getCurrentURL());
		return $this;
	}

	public function sleep($seconds)
	{
		sleep($seconds);
		return $this;
	}

	public function clearCookies()
	{
		$this->driver->manage()->deleteAllCookies();
		return $this;
	}

	public function executeScript($script, array $args = array())
	{
		return $this->driver->executeScript($script, $args);
	}

	public function takeScreenshot($save_as = null)
	{
		$this->driver->takeScreenshot($save_as);
		return $this;
	}

	public function curl($url = null, $options = array())
	{
		if ($url === null)
			$url = $this->getCurrentURL();

		$ch = curl_init($url);
		
		$defaults = [CURLOPT_RETURNTRANSFER => 1];
		
		// Can't use array_merge here cuz keys are numeric
		foreach ($options as $option => $value)
		{
			$defaults[$option] = $value;
		}
		$options = $defaults;

		foreach ($options as $option => $value)
		{
			curl_setopt($ch, $option, $value);
		}

		$cookies = [];
		foreach ($this->driver->manage()->getCookies() as $cookie)
		{
			$cookies[] = $cookie['name'].'='.$cookie['value'];
		}
		$cookies = implode(';', $cookies);

		curl_setopt($ch, CURLOPT_COOKIE, $cookies);
		$ret = curl_exec($ch);
		curl_close($ch);
		
		return $ret;
	}
}
