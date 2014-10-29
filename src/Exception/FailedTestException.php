<?php

namespace PrestaShop\PSTAF\Exception;

class FailedTestException extends \Exception
{
    public function marksUndeniableTestFailure()
    {
        return true;
    }
}
