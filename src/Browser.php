<?php

namespace PrestaShop;

class Browser
{
	private $driver;

	public function __construct()
	{
		$host = 'http://localhost:4444/wd/hub';

		$this->driver = \RemoteWebDriver::create($host, \DesiredCapabilities::firefox());
	}

	public function __destruct()
	{
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
	protected function find($selector, $options = [])
	{
		$unique = (isset($options['unique']) && !$options['unique']);
		$tos = 'cssSelector';
		$e =  $this->driver->findElement(\WebDriverBy::$tos($selector));

		if (count($e) > 1 && $unique)
			throw new AmbiguousMatchException();

		if ($unique && count($e) === 1)
			return $e[0];

		return $e;
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
	}
}
