<?php

namespace PrestaShop\PSTAF\Browser;

interface ElementInterface
{
	public function getAttribute($attributeName);

	public function getValue();

	public function fillIn($value);

	public function getText();

	public function find($selector, array $options = array());

	public function getTagName();

	public function isDisplayed();

	public function isSelected();

	public function click();
}