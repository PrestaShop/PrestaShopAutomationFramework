<?php

namespace PrestaShop;

use PrestaShop\Helper\FileSystem as FS;

class ConfigurationFile implements Util\DataStoreInterface
{
	private $path;
	private $options;
	private static $instances = [];

	public function __construct($path)
	{
		$this->path = $path;
		$this->options = new Util\DataStore();

		if (file_exists($path))
		{
			$data = json_decode(file_get_contents($path), true);
			$this->update($data);
		}
	}

	public function update($options)
	{
		foreach ($options as $key => $value)
		{
			$this->set($key, $value);
		}
		return $this;
	}

	public function save()
	{
		file_put_contents($this->path, json_encode($this->options->toArray(), JSON_PRETTY_PRINT));
	}

	public function get($value)
	{
		return $this->options->get($value);
	}

	public function set($key, $value)
	{
		$this->options->set($key, $value);
		return $this;
	}

	public function toArray()
	{
		return $this->options->toArray();
	}

	public static function getInstance($from_specific_path = null, $new = false)
	{
		$path = $from_specific_path ? $from_specific_path : 'pstaf.conf.json';

		$base_path = dirname(realpath($path));

		if (!isset(static::$instances[$path]) || $new)
		{
			$conf = new ConfigurationFile($path);
			if (!$conf->get("shop.filesystem_path"))
			{
				$conf->set("shop.filesystem_path", $base_path);
			}

			$filesystem_path = $conf->get('shop.filesystem_path');
			if (FS::isRelativePath($filesystem_path))
				$conf->set('shop.filesystem_path', realpath(FS::join($base_path, $filesystem_path)));

			$path_to_web_root = $conf->get('shop.path_to_web_root');
			if (FS::isRelativePath($path_to_web_root))
				$conf->set('shop.path_to_web_root', realpath(FS::join($base_path, $path_to_web_root)));			

			static::$instances[$path] = $conf;
		}

		return static::$instances[$path];
	}
}
