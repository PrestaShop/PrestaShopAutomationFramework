<?php

namespace PrestaShop\Helper;

class FileSystem
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

	public static function exists()
	{
		return file_exists(call_user_func_array(__NAMESPACE__.'\FileSystem::join', func_get_args()));
	}
}
