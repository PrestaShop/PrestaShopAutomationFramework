<?php

namespace PrestaShop;

class ConfigurationFile implements Util\DataStoreInterface
{
	private $path;
	private $options;

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

	public static function getFromCWD()
	{
		static $instance = null;
		if (!$instance)
			$instance = new ConfigurationFile('pstaf.conf.json');
		return $instance;
	}
}
