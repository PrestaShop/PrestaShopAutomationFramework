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

		if (!$this->get("shop.filesystem_path"))
		{
			$this->set("shop.filesystem_path", realpath(dirname($this->path)));
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

	/**
	 * Returns a key that may represent a relative path
	 * relative to $this->path as an absolute path
	 * @param  boolean $ensure_exists throw exception if final path does not exist
	 * @return string
	 */
	public function getAsAbsolutePath($key, $ensure_exists = true)
	{
		$path = $this->get($key);
		if (!$path)
			throw new \Exception("Configuration key `$key` not found in `{$this->path}`.");

		if (!FS::isAbsolutePath($path))
			$path = realpath(FS::join(dirname($this->path), $path));

		if ($ensure_exists && !file_exists($path))
			throw new \Exception("File or folder `$path` doesn't exist!");

		return $path;
	}

	public static function getDefaultPath()
	{
		return 'pstaf.conf.json';
	}
}
