<?php

namespace PrestaShop\PSTAF\Util;

interface DataStoreInterface
{
    public function get($key);
    public function set($key, $value);
    public function toArray();
}
