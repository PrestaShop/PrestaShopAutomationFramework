<?php

namespace PrestaShop\PSTAF\Helper;

class Spinner
{
    private $timeout_in_seconds;
    private $interval_in_milliseconds;
    private $error_message;

    private $passthrough = [];

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
                if (!$this->error_message) {
                    $this->error_message = $e->getMessage();
                }
                if (!$allowExceptions || $this->passesThrough($e)) {
                    break;
                }
            }
            usleep($this->interval_in_milliseconds * 1000);
        } while (time() <= $maxTime);

        throw new \PrestaShop\PSTAF\Exception\SpinAssertException($this->error_message);
    }

    public function assertNoException(callable $cb)
    {
        $maxTime = time() + $this->timeout_in_seconds;
        for (;;) {
            try {
                return call_user_func($cb);
            } catch (\Exception $e) {
                if (time() > $maxTime || $this->passesThrough($e)) {
                    throw $e;
                }
            }
            usleep($this->interval_in_milliseconds * 1000);
        }
    }

    public function addPassthroughExceptionClass($class)
    {
        $this->passthrough[] = $class;

        return $this;
    }

    public function passesThrough(\Exception $e)
    {
        foreach ($this->passthrough as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
