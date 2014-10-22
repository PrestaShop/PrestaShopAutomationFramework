<?php

namespace PrestaShop\Exception;

class FailedTestException extends \Exception
{
	public function marksUndeniableTestFailure() {
		return true;
	}
}