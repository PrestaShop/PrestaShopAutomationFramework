<?php

namespace PrestaShop\PSTAF\Exception;

class InvalidParameterException extends \Exception
{
    public function marksUndeniableTestFailure()
    {
        return true;
    }
}
