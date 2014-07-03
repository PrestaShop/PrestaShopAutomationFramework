<?php

namespace PrestaShop\Helper;

class URL
{
	public static function getParameter($url, $name)
	{
		$query = parse_url($url, PHP_URL_QUERY);
		$params = [];
		parse_str($query, $params);
		return isset($params[$name]) ? $params[$name] : null;
	}

	public static function build(array $parts)
	{
		$url = '';

		$url .= $parts['scheme'];
		$url .= '://';
		$url .= $parts['host'];
		$url .= $parts['path'];
		$url .= '?'.$parts['query'];

		return $url;
	}

	/**
	* Returns $url with query parameters not in $paramsToKeep stripped and those
	* in $paramsToAdd added.
	* $paramsToKeep is a "flat" array e.g. ['id', 'token']
	* $paramsToAdd is an associative array e.g. ['language' => 'en', 'id' => 42]
	*/
	public static function filterParameters($url, array $paramsToKeep, array $paramsToAdd)
	{
		$parts = parse_url($url);
		$current_params = [];
		$new_params = [];

		parse_str($parts['query'], $current_params);

		foreach ($paramsToKeep as $name)
		{
			if (isset($current_params[$name]))
			{
				$new_params[$name] = $current_params[$name];
			}
		}

		$new_params = array_merge($new_params, $paramsToAdd);

		$parts['query'] = http_build_query($new_params);
		return static::build($parts);
	}
}
