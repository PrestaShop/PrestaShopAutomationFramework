<?php

namespace PrestaShop\PSTAF\Browser;

interface ElementInterface
{
	public function getAttribute($attributeName);

	public function getValue();

	public function fillIn($value);

	public function sendKeys($keys);

	public function getText();

	public function find($selector, array $options = array());

	public function all($selector);

	public function getTagName();

	public function isDisplayed();

	public function isEnabled();

	public function isSelected();

	public function click();
}