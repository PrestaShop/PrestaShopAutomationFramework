<?php

namespace PrestaShop\Helper;

class Spinner
{
	private $timeout_in_seconds;
	private $interval_in_milliseconds;
	private $error_message;

	public function __construct(
		$error_message = 'Could not assert something.',
		$timeout_in_seconds = 5,
		$interval_in_milliseconds = 500
	)
	{
		$this->timeout_in_seconds = $timeout_in_seconds;
		$this->interval_in_milliseconds = $interval_in_milliseconds;

		$this->error_message = $error_message;
	}

	public function assertBecomesTrue(callable $truthy_returner)
	{
		$elapsed = 0;
		while ($elapsed < $this->timeout_in_seconds)
		{
			try {
				if (call_user_func($truthy_returner))
					return true;
			} catch (\Exception $e) {
				if ($elapsed >= $this->timeout_in_seconds)
					throw $e;
			}
			usleep($this->interval_in_milliseconds * 1000);
			$elapsed += $this->interval_in_milliseconds / 1000;
		}

		throw new \PrestaShop\Exception\SpinAssertException($this->error_message);
	}

	public function assertNoException(callable $cb)
	{
		$elapsed = 0;
		while (true)
		{
			try {
				return call_user_func($cb);
			} catch (\Exception $e) {
				if ($elapsed >= $this->timeout_in_seconds)
					throw $e;
			}
			usleep($this->interval_in_milliseconds * 1000);
			$elapsed += $this->interval_in_milliseconds / 1000;
		}
	}
}