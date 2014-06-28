<?php

namespace PrestaShop;

class ConfigurationFile
{
	private $path;
	private $options;

	public function __construct($path)
	{
		$this->path = $path;

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
			$this->options[$key] = $value;
		}
		return $this;
	}

	public function save()
	{
		file_put_contents($this->path, json_encode($this->options, JSON_PRETTY_PRINT));
	}
}
