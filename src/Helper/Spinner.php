<?php

namespace PrestaShop\Helper;

class Spinner
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

	public function assertBecomesTrue(callable $truthy_returner)
	{
		$elapsed = 0;
		$ok = false;
		$exception = null;

		do {
			try {
				$ok = call_user_func($truthy_returner);
			} catch (\Exception $e) {
				$ok = false;
				$exception = $e;
			}

			if ($ok)
				return;

			usleep($this->interval_in_milliseconds * 1000);
			$elapsed += $this->interval_in_milliseconds / 1000;
		} while (!$ok && $elapsed < $this->timeout_in_seconds);

		if (!$ok)
		{
			if ($this->error_message)
			{
				throw new \PrestaShop\Exception\SpinAssertException($this->error_message);
			}
			elseif ($exception)
			{
				throw $exception;
			}
			else
			{
				throw new \PrestaShop\Exception\SpinAssertException('Callback never returned true, but did not produce an exception either.');
			}
		}
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