<?php

namespace PrestaShop\Helper;

class SpinAssert
{
	private $timeout_in_seconds;
	private $interval_in_milliseconds;
	private $error_message;

	public function __construct(
		$error_message = 'Could not SpinAssert something.',
		$timeout_in_seconds = 5,
		$interval_in_milliseconds = 500
	)
	{
		$this->timeout_in_seconds = $timeout_in_seconds;
		$this->interval_in_milliseconds = $interval_in_milliseconds;

		$this->error_message = $error_message;
	}

	public function becomesTrue(callable $truthy_returner)
	{
		$elapsed = 0;
		$ok = false;
		do {
			try {
				$ok = call_user_func($truthy_returner);
			} catch (\Exception $e) {
				$ok = false;
			}


			usleep($this->interval_in_milliseconds * 1000);
			$elapsed += $this->interval_in_milliseconds / 1000;
		} while (!$ok && $elapsed < $this->timeout_in_seconds);

		if (!$ok)
		{
			throw new \PrestaShop\Exception\SpinAssertException($this->error_message);
		}
	}
}