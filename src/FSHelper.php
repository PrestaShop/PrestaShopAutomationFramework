<?php

namespace PrestaShop;

class FSHelper
{
	public static function join()
	{
		$separator = DIRECTORY_SEPARATOR;

		$args = func_get_args();
		$base = $args[0];
		for ($i = 1; $i < count($args); $i++)
		{
			$base = rtrim($base, $separator).$separator.ltrim($args[$i], $separator);
		}
		return $base;
	}
}
