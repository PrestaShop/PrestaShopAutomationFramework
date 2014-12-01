<?php

namespace PrestaShop\PSTAF;

use PrestaShop\PSTAF\Helper\Spinner;
use PrestaShop\PSTAF\Helper\URL;

class Browser
{
    private $driver;
    private $quitted = false;
    private $logMessagesSeen = [];

    private $screenshotsDir = false;

    // If this is >= 0, auto screenshots are active, if this is < 0,
    // they're disabled.
    // This is used to prevent double screenshotting when functions call one another.
    private $screenshots = 0;

    public function __construct($seleniumSettings)
    {
        $caps = [
            'browserName' => 'firefox'
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
        // This is to ensure we close the windows
        if (!$this->quitted)
            $this->quit();
    }

    public function recordScreenshots($dir)
    {
        if (!$dir) {
            $this->screenshotsDir = false;
        } else {
            $this->screenshotsDir = $dir;
            $this->screenshotNumber = 0;
            $this->screenshots = 0;
        }
    }

    /**
     * Call with 'false' to disa le autoScreenshotting temporarily,
     * with 'true' to reenable it, and with null or anything else
     * to take a screenshot without enabling/disabling them
     */
    public function autoScreenshot($yes = true)
    {
        if ($yes === true) {
            $this->screenshots += 1;
        } elseif ($yes === false) {
            $this->screenshots -= 1;
        }

        if ($this->screenshotsDir && $this->screenshots >= 0) {
            $this->screenshotNumber += 1;
            $n = sprintf("%'06d) ", $this->screenshotNumber);
            $base = $this->screenshotsDir.DIRECTORY_SEPARATOR.$n.strftime("%a %d %b %Y, %H;%M;%S");
            $filename = $base.'.png';
            $this->takeScreenshot($filename);
            $metaData = [];

            $metaData['Current URL'] = $this->getCurrentURL();
            $metaData['Browser Log'] = [];

            $log = $this->driver->manage()->getLog('browser');
            foreach ($log as $entry) {
                $hash = md5(serialize($entry));
                if (empty($this->logMessagesSeen[$hash])) {
                    $this->logMessagesSeen[$hash] = true;
                    $metaData['Browser Log'][] = $entry;
                }
            }

            $metaDataFile = $base.'.json';
            file_put_contents($metaDataFile, json_encode($metaData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    public function quit()
    {
        $this->quitted = true;
        $this->driver->quit();
    }

    /**
     * Utility function: do something with the browser,
     * and then reload the current URL.
     * This is useful if you need to grab an info from another
     * page while not wanting to interrupt your current browsing
     * flow.
     * The function will return the value of your callback.
     */
    public function doThenComeBack(callable $callback)
    {
        $url = $this->getCurrentURL();

        $res = $callback();

        $this->visit($url);

        return $res;
    }

    /**
	* Wait for user input - useful when debugging.
	*/
    public function waitForUserInput()
    {
        echo "\n\n<<-- [[PAUSED]] hit RETURN to keep going -->>\n\n";
        if(trim(fgets(fopen("php://stdin","r"))) != chr(13)) return $this;

        return $this;
    }

    /**
	* Visit a URL.
    * The optional basic_auth array contains user and pass keys,
    * they will be injected into the URL if the array is provided.
	*/
    public function visit($url, array $basic_auth = null)
    {
        if ($basic_auth) {
            $url = preg_replace('/^(\w+:\/\/)/', '\1'.$basic_auth['user'].':'.$basic_auth['pass'].'@', $url);
        }
        $this->driver->get($url);

        $this->autoScreenshot(null);

        return $this;
    }

    /**
     * Gets the source code of the page.
     */
    public function getPageSource()
    {
        return $this->driver->getPageSource();
    }

    /**
     * Gets the value of an attribute for a given selector.
     */
    public function getAttribute($selector, $attribute)
    {
        try {
            $this->screenshots -= 1;
            return $this->find($selector)->getAttribute($attribute);
        } finally {
            $this->screenshots += 1;
        }
    }

    /**
     * Gets the value of an input for the given selector.
     */
    public function getValue($selector)
    {
        return $this->getAttribute($selector, 'value');
    }

    public function getSelectedValue($selector)
    {
        $select = new \WebDriverSelect($this->find($selector));
        $option = $select->getFirstSelectedOption();
        if ($option) {
            return $option->getAttribute('value');
        } else {
            return null;
        }
    }

    public function getSelectedValues($selector)
    {
        $select = new \WebDriverSelect($this->find($selector));
        $options = $select->getAllSelectedOptions();
        return array_map(function ($option) {
            return $option->getAttribute('value');
        }, $options);
    }

    /**
     * Gets the VISIBLE text for a given selector.
     */
    public function getText($selector)
    {
        try {
            $this->screenshots -= 1;
            return $this->find($selector)->getText();
        } finally {
            $this->screenshots += 1;
        }
    }

    /**
	* Find element(s).
    *
    * Selectors are interpreted as CSS by default,
    * example: ".main ul li"
    * 
    * To use an xpath selector, prefix it with {xpath},
    * for instance "{xpath}}//div".
    *
    * The options you can provide are:
    * - unique: boolean, defaults to true - whether or not the element you're looking for is unique
    * - wait: boolean, defaults to true - wait for the element to show up if it is not immediately there
    *
    * @return native \RemoteWebDriverElement or elements
    * 
	*/
    public function find($selector, $options = [])
    {
        try {
            $this->autoScreenshot(false);

            $unique = !isset($options['unique']) || $options['unique'];

            $m = [];
            if (preg_match('/^{xpath}(.*)$/', $selector, $m)) {
                $selector = $m[1];
                $tos = 'xpath';
            } else
                $tos = 'cssSelector';

            $method = $unique ? 'findElement' : 'findElements';

            if (isset($options['wait']) && $options['wait'] === false) {  
                return $this->driver->$method(\WebDriverBy::$tos($selector));
            }

            $spin = new Spinner('Could not find element.', 5);

            return $spin->assertNoException(function () use ($method, $tos, $selector) {
                return $this->driver->$method(\WebDriverBy::$tos($selector));
            });
        } finally {
            $this->autoScreenshot();
        }
    }

    public function hasVisible($cssSelector)
    {
        try {
            $e = $this->driver->findElement(\WebDriverBy::cssSelector($cssSelector));
            return $e->isDisplayed();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Find all elements matching the selector.
     * This is a shortcut for find($selector, ['unique' => false]);
     */
    public function all($selector)
    {
        return $this->find($selector, ['unique' => false]);
    }

    /**
     * Count elements matching a selector.
     */
    public function count($selector)
    {
        try {
            $this->screenshots -= 1;
            return count($this->find($selector, ['unique' => false]));
        } finally {
            $this->screenshots += 1;
        }
    }

    public function ensureElementIsOnPage($selector, $exception=null)
    {
        try {
            $this->autoScreenshot(false);
            $this->find($selector);
        } catch (\Exception $e) {
            if ($exception)
                throw $exception;
            else
                throw $e;
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
     * Clicks an element by selector.
     * Waits a bit if it is not there.
     */
    public function click($selector, array $options = array())
    {
        $options = array_merge(['screenshot' => true], $options);

        try {
            if ($options['screenshot']) {
                $this->autoScreenshot(false);
            } else {
                --$this->screenshots;
            }

            $element = $this->find($selector);
            $element->click();
        } finally {
            if ($options['screenshot']) {
                $this->autoScreenshot();
            } else {
                ++$this->screenshots;
            }
        }

        return $this;
    }

    /**
     * Emulates a mouseover action.
     */
    public function hover($selector)
    {
        $this->autoScreenshot(false);

        $element = $this->find($selector);
       
        try {
            $this->driver->action()->moveToElement($element)->perform();
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
     * Clicks the first enabled button with the given name.
     */
    public function clickButtonNamed($name)
    {
        return $this->clickFirstVisible("button[name=$name]");
    }

    public function clickFirstVisible($selector)
    {
        try {
            $this->autoScreenshot(false);

            $buttons = $this->find($selector, ['unique' => false]);
            foreach ($buttons as $button) {
                if ($button->isDisplayed() && $button->isEnabled()) {
                    $button->click();

                    return $this;
                }
            }
            throw new \Exception("Could not find any visible thingy like: $selector");
        } finally {
            $this->autoScreenshot();
        }
    }

    /**
     * Clicks the label that has the given "for" attribute.
     * It is often more robust to click labels instead of
     * checkboxes, so this convenience method is provided.
     */
    public function clickLabelFor($for)
    {
        try {
            $this->autoScreenshot(false);
            $element = $this->find("label[for=$for]");
            $element->click();
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
     * Fills in an input with the given value.
     * Will wait a bit for the element to show up
     * if it is not immediately available.
     */
    public function fillIn($selector, $value)
    {
        try {
            $this->autoScreenshot(false);
            $element = $this->find($selector);
            $element->click();
            $element->clear();
            $this->driver->getKeyboard()->sendKeys($value);
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
     * Convenience method to fill in the value of an input
     * if we got the element as a native remote element.
     */
    public function setElementValue($element, $value)
    {
        try {
            $this->autoScreenshot(false);
            $element->click();
            $element->clear();
            $element->sendKeys($value);
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
     * Helper method to set the file for a file input.
     * This takes care of making it visible if it is not.
     * This strange behaviour is needed to allow file upload
     * with widgets that mask the original input.
     */
    public function setFile($selector, $path)
    {
        try {
            $this->autoScreenshot(false);
            $element = $this->find($selector);
            if (!$element->isDisplayed()) {
                $this->executeScript("
    				arguments[0].style.setProperty('display', 'inherit', 'important');
    				arguments[0].style.setProperty('visibility', 'visible', 'important');
    				arguments[0].style.setProperty('width', 'auto', 'important');
    				arguments[0].style.setProperty('height', 'auto', 'important');
    			", [$element]);
            }
            $element->sendKeys($path);
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
     * Simulates keypresses.
     */
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
        try {
            $this->autoScreenshot(false);
            if ($value === null)
                return $this;

            $elt = $this->find($selector);

            $select = new \WebDriverSelect($elt);
            $select->selectByValue($value);
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    public function getSelectText($selector)
    {
        try {
            $this->autoScreenshot(false);

            $elt = $this->find($selector);

            $select = new \WebDriverSelect($elt);
            return $select->getFirstSelectedOption()->getText();
        } finally {
            $this->autoScreenshot();
        }
    }

    /**
	 * Select by value in a multiple select.
	 * Options is an array of values.
	 */
    public function multiSelect($selector, $options)
    {
        try {
            $this->autoScreenshot(false);
            $elem = $this->find($selector);
            $elem->click();
            sleep(1);    // strangely, this is needed,
                         // otherwise Selenium throws a StaleElementException
                         // I don't have the faintest idea why

            $option_elts = $elem->findElements(\WebDriverBy::cssSelector('option'));

            $this->driver->getKeyboard()->pressKey(\WebDriverKeys::CONTROL);

            $matched = 0;

            foreach ($option_elts as $opt) {
                if (in_array($opt->getAttribute('value'), $options)) {
                    $matched += 1;
                    if (!$opt->isSelected()) {
                        $opt->click();
                    }
                } elseif ($opt->isSelected()) {
                    $opt->click();
                }
            }

            $this->driver->getKeyboard()->releaseKey(\WebDriverKeys::CONTROL);

            if ($matched !== count($options))
                throw new \Exception('Could not select all values.');
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
	* Get Select Options as associative array
	*/
    public function getSelectOptions($selector)
    {
        try {
            $this->screenshots -= 1;
            $options = [];
            $elem = $this->find($selector);
            $elem->click();
            // Multiselects apparently need us to slow down
            if ($elem->getAttribute('multiple'))
                sleep(1);
            $select = new \WebDriverSelect($elem);
            foreach ($select->getOptions() as $opt) {
                $options[$opt->getAttribute('value')] = $opt->getText();
            }
            $this->sendKeys(\WebDriverKeys::ESCAPE);
        } finally {
            $this->screenshots += 1;
        }

        return $options;
    }

    /**
	* Select by value in a JQuery chosen select.
	*/
    public function jqcSelect($selector, $value)
    {
        try {
            $this->autoScreenshot(false);
            /**
    		* Spin this test, because since JQuery is involved, the DOM is likely not to be ready.
    		*/
            $spinner = new Spinner(null, 5, 1000);

            $spinner->assertBecomesTrue(function () use ($selector, $value) {
                $this->_jqcSelect($selector, $value);

                return true;
            });
        } finally {
            $this->autoScreenshot();
        }

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
        if (count($optgroups) > 0) {
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

        foreach ($option_containers as $option_container) {
            if ($option_container->getTagName() === 'optgroup') {
                $n += 1;
            }

            $optionElements = $option_container->findElements(\WebDriverBy::tagName('option'));

            foreach ($optionElements as $element) {
                $v = $element->getAttribute('value');
                if ($v == $value) {
                    $chosen->findElement(\WebDriverBy::cssSelector('*[data-option-array-index="'.$n.'"]'))->click();

                    return $this;
                }

                $n += 1;
            }
        }

        throw new \PrestaShop\PSTAF\Exception\SelectValueNotFoundException();
    }

    /**
	* Check or uncheck a checkbox
	*/
    public function checkbox($selector, $on_off)
    {
        try {
            $this->autoScreenshot(false);
            $cb = $this->find($selector);
            if (($on_off && !$cb->isSelected()) || (!$on_off && $cb->isSelected()))
                $cb->click();
        } finally {
            $this->autoScreenshot();
        }

        return $this;
    }

    /**
	* Wait for element to appear
	*/
    public function waitFor($selector, $timeout_in_second = null, $interval_in_millisecond = null)
    {
        try {
            $this->autoScreenshot(false);
            $wait = new \WebDriverWait($this->driver, $timeout_in_second, $interval_in_millisecond);
            $wait->until(function ($driver) use ($selector) {
                try {
                    $e = $this->find($selector);

                    return $e->isDisplayed();
                } catch (\Exception $e) {
                    return false;
                }

                return true;
            });
        } finally {
            $this->autoScreenshot();
        }

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

        return URL::getParameter($url, $param);
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

    /**
     * Convenience method to wait a bit and keep chaining methods.
     */
    public function sleep($seconds)
    {
        sleep($seconds);

        return $this;
    }

    /**
     * Clears the browser's cookies.
     */
    public function clearCookies()
    {
        $this->driver->manage()->deleteAllCookies();

        return $this;
    }

    /**
     * Executes javascript code in the context of the current page.
     * When executing a function, arguments can be provided as an associative
     * array in the $args argument.
     */
    public function executeScript($script, array $args = array())
    {
        try {
            return $this->driver->executeScript($script, $args);
        } finally {
            $this->autoScreenshot(null);
        }
    }

    public function acceptAlert()
    {
        $spinner = new Spinner('Did not find alert.');

        $spinner->assertNoException(function() {
            $alert = $this->driver->switchTo()->alert();
            $alert->accept();
        });

        return $this;
    }

    public function switchToIFrame($name)
    {
        $this->driver->switchTo()->frame($name);
        return $this;
    }

    public function switchToDefaultContent()
    {
        $this->driver->switchTo()->defaultContent();
        return $this;
    }

    /**
     * Takes a screenshot, saves it to the "save_as" path.
     */
    public function takeScreenshot($save_as = null)
    {
        $this->driver->takeScreenshot($save_as);

        return $this;
    }

    /**
     * Executes a curl request with the same cookies
     * as the browser.
     */
    public function curl($url = null, $options = array())
    {
        if ($url === null)
            $url = $this->getCurrentURL();

        $ch = curl_init($url);

        $defaults = [CURLOPT_RETURNTRANSFER => 1];

        // Can't use array_merge here cuz keys are numeric
        foreach ($options as $option => $value) {
            $defaults[$option] = $value;
        }
        $options = $defaults;

        foreach ($options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $cookies = [];
        foreach ($this->driver->manage()->getCookies() as $cookie) {
            $cookies[] = $cookie['name'].'='.$cookie['value'];
        }
        $cookies = implode(';', $cookies);

        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
        $ret = curl_exec($ch);
        curl_close($ch);

        return $ret;
    }
}
