<?php

namespace PrestaShop\PSTAF\Helper;

class Spinner
{
    private $timeout_in_seconds;
    private $interval_in_milliseconds;
    private $error_message;

    public function __construct(
        $error_message = 'Repeated assertion test failed after maximum waiting time was reached.',
        $timeout_in_seconds = 5,
        $interval_in_milliseconds = 500
    )
    {
        $this->timeout_in_seconds = $timeout_in_seconds;
        $this->interval_in_milliseconds = $interval_in_milliseconds;

        $this->error_message = $error_message;
    }

    public function assertBecomesTrue(callable $truthy_returner, $allowExceptions = true)
    {
        $maxTime = time() + $this->timeout_in_seconds;
        do {
            try {
                if (($val = call_user_func($truthy_returner))) {
                    return $val;
                }
            } catch (\Exception $e) {
                if (time() > $maxTime || !$allowExceptions) {
                    throw $e;
                } else {
                    $this->error_message = $e->getMessage();
                }
            }
            usleep($this->interval_in_milliseconds * 1000);
        } while ( time() <= $maxTime );

        throw new \PrestaShop\PSTAF\Exception\SpinAssertException($this->error_message);
    }

    public function assertNoException(callable $cb)
    {
        $maxTime = time() + $this->timeout_in_seconds;
        do {
            try {
                return call_user_func($cb);
            } catch (\Exception $e) {
                if (time() > $maxTime) {
                    throw $e;
                } else {
                    $this->error_message = $e->getMessage();
                }
            }
            usleep($this->interval_in_milliseconds * 1000);
        } while ( time() <= $maxTime );

        throw new \PrestaShop\PSTAF\Exception\SpinAssertException($this->error_message);
    }
}
