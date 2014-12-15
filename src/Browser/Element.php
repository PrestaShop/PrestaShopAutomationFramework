<?php

namespace PrestaShop\PSTAF\Browser;

use WebDriverElement;

class Element implements ElementInterface
{
	private $nativeElement;
	private $browser;

	public function __construct(WebDriverElement $nativeElement, $browser)
	{
		$this->nativeElement = $nativeElement;
		$this->browser = $browser;
	}

	public function getAttribute($attributeName)
	{
		return $this->nativeElement->getAttribute($attributeName);
	}

	public function getValue()
	{
		return $this->getAttribute('value');
	}

	public function fillIn($value)
	{
		$this->nativeElement->clear()->sendKeys($value);

		return $this;
	}

	public function getText()
	{
		return trim($this->nativeElement->getText());
	}

	public function find($selector, array $options = array())
	{
		$options['baseElement'] = $this;

		return $this->browser->find($selector, $options);
	}

	public function getTagName()
	{
		return $this->nativeElement->getTagName();
	}

	public function isDisplayed()
	{
		return $this->nativeElement->isDisplayed();
	}

	public function isSelected()
	{
		return $this->nativeElement->isSelected();
	}

	public function getNativeElement()
	{
		return $this->nativeElement;
	}

	public function click()
	{
		$this->nativeElement->click();

		return $this;
	}
}