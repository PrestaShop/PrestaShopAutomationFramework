<?php

namespace PrestaShop\Helper;

class FileSystem
{
	public static function join()
	{
		$separator = DIRECTORY_SEPARATOR;

		$args = func_get_args();
		$base = $args[0];
		
		if (!$base)
			$base = '.';

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

	public static function lsRecursive($dir, array $exclude_regexps = array())
	{
		$files = array();

		foreach (scandir($dir) as $entry)
		{
			if ($entry === '.' || $entry === '..')
				continue;

			foreach ($exclude_regexps as $exp)
			{
				if (preg_match($exp, $entry))
					continue 2;
			}


			$path = realpath(static::join($dir, $entry));
			
			if (is_link($path))
				continue;

			$files[] = $path;

			if (is_dir($path))
				$files = array_merge($files, static::lsRecursive($path));
		}

		sort($files);

		return $files;
	}
}
