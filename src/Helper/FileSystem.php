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

	public static function rtrimSeparator($path)
	{
		return rtrim($path, DIRECTORY_SEPARATOR);
	}

	public static function exists()
	{
		return file_exists(call_user_func_array(__NAMESPACE__.'\FileSystem::join', func_get_args()));
	}

	private static function _lsRecursive($dir, array $exclude_exceptions = array(), array $exclude_regexps = array(), $topLevelDirectory)
	{
		$files = array();

		foreach (scandir($dir) as $entry)
		{
			$path = realpath(static::join($dir, $entry));

			if ($entry === '.' || $entry === '..')
				continue;

			$relpath = substr($path, strlen($topLevelDirectory));

			$dont_exclude = false;

			foreach ($exclude_exceptions as $exp)
			{
				if (preg_match($exp, $relpath))
				{
					$dont_exclude = true;
					break;
				}
			}

			if (!$dont_exclude)
			{
				foreach ($exclude_regexps as $exp)
				{
					if (preg_match($exp, $relpath))
						continue 2;
				}
			}
			
			if (is_link($path))
				continue;

			$files[] = $path;

			if (is_dir($path))
				$files = array_merge($files, static::_lsRecursive($path, $exclude_exceptions, $exclude_regexps, $topLevelDirectory));
		}

		sort($files);

		return $files;
	}

	public static function lsRecursive($dir, array $exclude_exceptions = array(), array $exclude_regexps = array())
	{
		return static::_lsRecursive($dir, $exclude_exceptions, $exclude_regexps, $dir);
	}
}
