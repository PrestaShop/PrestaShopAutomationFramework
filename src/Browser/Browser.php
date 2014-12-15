<?php

namespace PrestaShop\PSTAF\Browser;

use PrestaShop\PSTAF\Helper\Spinner;
use PrestaShop\PSTAF\Helper\URL;
use PrestaShop\PSTAF\Exception\ElementNotFoundException;
use PrestaShop\PSTAF\Exception\TooManyElementsFoundException;

use Exception;

use NoSuchElementException;
use RemoteWebDriver;
use WebDriverDimension;
use WebDriverBy;
use WebDriverSelect;
use WebDriverKeys;

class Browser // implements BrowserInterface
{
    // the underlying WebDriver
    private $driver;

    private $defaultTimeout = 5;
    private $defaultInterval = 500;

    public function __construct(array $settings = array())
    {
        $defaults = [
            'browserName' => 'firefox'
        ];

        $settings = array_merge($defaults, $settings);

        if (!isset($settings['host'])) {
            throw new Exception('Missing selenium host option.');
        }

        $host = $settings['host'];

        unset($settings['host']);

        $this->driver = RemoteWebDriver::create($host, $settings);

        $this->resizeWindow(1920, 1200);
    }

    public function quit()
    {
         $this->driver->quit();

         return $this;
    }

    private function before($function, $arguments)
    {

    }

    private function after($function, $arguments)
    {

    }

    private function wrap($function, $arguments)
    {
        $this->before($function, $arguments);

        try {
            return call_user_func_array([$this, '_' . $function], $arguments);
        } finally {
            $this->after($function, $arguments);
        }
    }
    
    public function resizeWindow($width, $height)
    {
        $this->driver->manage()->window()->setSize(new WebDriverDimension($width, $height));

        return $this;
    }

    public function setDefaultRetryTimeout($timeout)
    {
        $this->defaultTimeout = $timeout;
        return $this;
    }

    public function setDefaultRetryInterval($interval)
    {
        $this->defaultInterval = $interval;
        return $this;
    }

    public function getCurrentURL()
    {
        return $this->driver->getCurrentURL();
    }

    public function getPageSource()
    {
        return $this->driver->getPageSource();
    }

    public function getURLParameter($param)
    {
        $url = $this->getCurrentURL();

        return URL::getParameter($url, $param);
    }

    private function _visit($url, array $basic_auth = null)
    {
        if ($basic_auth) {
            $url = preg_replace('/^(\w+:\/\/)/', '\1'.$basic_auth['user'].':'.$basic_auth['pass'].'@', $url);
        }
        $this->driver->get($url);

        return $this;
    }

    public function visit($url, array $basic_auth = null)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    

    private function _find($selector, array $options = array())
    {
        $defaults = [
            'unique' => true,
            'wait'  => true,
            'baseElement' => null,
            'timeout' => $this->defaultTimeout,
            'interval' => $this->defaultInterval,
            'displayed' => null
        ];

        $options = array_merge($defaults, $options);

        $base = $this->driver;
        if ($options['baseElement']) {
            $base = $options['baseElement']->getNativeElement();
        }

        if (!$options['wait']) {
            $options['timeout'] = 0;
        }

        $finalSelector = $selector;
        $selectorType = 'cssSelector';

        if (preg_match('/^{xpath}(.*)$/', $selector, $m)) {
            $finalSelector = $m[1];
            $selectorType = 'xpath';
        }

        $spinner = new Spinner(null, $options['timeout'], $options['interval']);

        $spinner->addPassthroughExceptionClass('PrestaShop\PSTAF\Exception\TooManyElementsFoundException');

        try {
            return $spinner->assertNoException(function () use ($base, $selectorType, $finalSelector, $options) {
                $found = $base->findElements(WebDriverBy::$selectorType($finalSelector));

                $elements = array_map(function ($nativeElement) {
                    return new Element($nativeElement, $this);
                }, $found);

                if ($options['displayed'] !== null) {
                    $elements = array_filter($elements, function ($element) use ($options) {
                        return $element->isDisplayed() == $options['displayed'];
                    });
                }

                if (empty($elements)) {
                    throw new Exception('No element found.');
                }

                if ($options['unique'] && count($elements) > 1) {
                    throw new TooManyElementsFoundException();
                }

                if ($options['unique']) {
                    return $elements[0];
                } else {
                    return $elements;
                }

            });
        } catch (Exception $e) {
            
            if ($e instanceof TooManyElementsFoundException) {
                throw $e;
            }

            throw new ElementNotFoundException('Could not find element(s).', 1, $e);
        }
    }

    public function find($selector, array $options = array())
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _waitFor($selector, $timeout_in_second = 5, $interval_in_millisecond = 500)
    {
        $this->find($selector, [
            'unique' => false,
            'timeout' => $timeout_in_second,
            'interval' => $interval_in_millisecond,
            'displayed' => true
        ]);

        return $this;
    }

    public function waitFor($selector, $timeout_in_second = 5, $interval_in_millisecond = 500)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    public function all($selector)
    {
        try {
            return $this->find($selector, ['unique' => false]);
        } catch (ElementNotFoundException $e) {
            return [];
        }
    }

    public function count($selector)
    {
        return count($this->all($selector));
    }

    private function _getAttribute($selector, $attribute)
    {
        return $this->find($selector)->getAttribute($attribute);
    }

    public function getAttribute($selector, $attribute)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _fillIn($selector, $value)
    {
        $this->find($selector)->fillIn($value);
    }

    public function fillIn($selector, $value)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _getValue($selector)
    {
        return $this->getAttribute($selector, 'value');
    }

    public function getValue($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _getText($selector)
    {
        return $this->find($selector)->getText();
    }

    public function getText($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _select($selector, $value)
    {
        $element = $this->find($selector)->getNativeElement();
        $select = new WebDriverSelect($element);
        $select->selectByValue($value);

        return $this;
    }

    private function _getSelectOptions($selector)
    {
         $element = $this->find($selector)->click();
         if ($element->getAttribute('multiple')) {
             sleep(1); // seems needed, God knows why
         }
         $select = new WebDriverSelect($element->getNativeElement());
         foreach ($select->getOptions() as $option) {
             $options[$option->getAttribute('value')] = $option->getText();
         }
         $this->sendKeys(WebDriverKeys::ESCAPE);
         return $options;
    }

    public function getSelectOptions($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    public function select($selector, $value)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _getSelectedValue($selector)
    {
        $select = new WebDriverSelect($this->find($selector)->getNativeElement());

        $option = $select->getFirstSelectedOption();

        return $option->getAttribute('value');
    }

    public function getSelectedValue($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _getSelectedText($selector)
    {
        $select = new WebDriverSelect($this->find($selector)->getNativeElement());

        $option = $select->getFirstSelectedOption();

        return $option->getText();
    }

    public function getSelectedText($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _multiSelect($selector, array $values)
    {
        $element = $this->find($selector);
        $element->click();
        sleep(1);    // strangely, this is needed,
                     // otherwise Selenium throws a StaleElementException
                     // I don't have the faintest idea why

        $options = $element->find('option', ['unique' => false]);

        $this->driver->getKeyboard()->pressKey(WebDriverKeys::CONTROL);

        $matched = 0;

        foreach ($options as $option) {
            if (in_array($option->getValue(), $values)) {
                $matched++;
                if (!$option->isSelected()) {
                    $option->click();
                }
            } elseif ($option->isSelected()) {
                $option->click();
            }
        }

        $this->driver->getKeyboard()->releaseKey(WebDriverKeys::CONTROL);

        if ($matched !== count($values))
            throw new Exception('Could not select all values.');

        return $this;
    }

    public function multiSelect($selector, array $values)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _getSelectedValues($selector)
    {
        $select = new WebDriverSelect($this->find($selector)->getNativeElement());

        $options = $select->getAllSelectedOptions();

        return array_map(function ($option) {
            return $option->getAttribute('value');
        }, $options);
    }

    public function getSelectedValues($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _click($selector)
    {
        $this->find($selector)->click();

        return $this;
    }

    public function click($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _hover($selector)
    {
        $element = $this->find($selector)->getNativeElement();
        $this->driver->action()->moveToElement($element)->perform();

        return $this;
    }

    public function hover($selector)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }

    private function _sendKeys($keys)
    {
        $this->driver->getKeyboard()->sendKeys($keys);

        return $this;
    }

    public function sendKeys($keys)
    {
        return $this->wrap(__FUNCTION__, func_get_args());
    }
}
