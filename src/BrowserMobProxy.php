<?php

namespace PrestaShop\PSTAF;

class BrowserMobProxy
{
	const CAPTURE_HEADERS = 1;
	const CAPTURE_CONTENT = 2;
	const CAPTURE_BINARY_CONTENT = 4;

	private $masterURL;
	private $port = null;

	public function __construct($masterURL)
	{
		$this->masterURL = $masterURL;

		$this->start();
	}

	public function __destruct()
	{
		if ($this->port) {
			$this->stop();
		}
	}

	private function start()
	{
		$this->port = $this->request('POST')['port'];

		$this->mapEtcHosts();

		return $this;
	}

	private function stop()
	{
		$this->request('DELETE', $this->port);

		return $this;
	}

	private function request($method, $path = '/', $payload = null)
	{
		$url = rtrim($this->masterURL, '/') . '/proxy/' . ltrim($path, '/');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$method = strtoupper($method);

		if ($method === 'GET') {
			// OK, that's the default
		} elseif ($method === 'PUT') {
			curl_setopt($ch, CURLOPT_PUT, true);
		} elseif ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
		} elseif ($method === 'DELETE') {
			// curl, srsly?
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}

		if ($payload && in_array($method, ['PUT', 'POST'])) {
			if (is_array($payload)) {
				$payload = json_encode($payload);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		}

		$response = curl_exec($ch);

		curl_close($ch);

		return json_decode($response, true);
	}

	public function getURL()
	{
		$url = $this->masterURL;
		$url = rtrim($url, '/');
		$url = preg_replace('#^\w+://#', '', $url);
		$url = preg_replace('/:\d+$/', '', $url);
		return $url
		 . ':' . $this->port;
	}

	public function remapHost($host, $ip)
	{
		$this->request('POST', $this->port . '/hosts', [$host => $ip]);

		return $this;
	}

	public function remapHosts(array $hostsAndIps)
	{
		$this->request('POST', $this->port . '/hosts', $hostsAndIps);

		return $this;
	}

	public function mapEtcHosts()
	{
		$src = '/etc/hosts';
		$hostsAndIps = [];

		if (file_exists($src)) {
			$data = file_get_contents($src);
			$lines = preg_split('/\n+/', $data);
			foreach ($lines as $line) {
				$line = trim($line);
				if (!$line || $line[0] === '#') {
					continue;
				}
				$parts = preg_split('/\s+/', $line);

				if (preg_match('/^\d+(\.\d+){3}$/', $parts[0])) {
					$ip = $parts[0];
					for ($i = 1; $i < count($parts); $i++) {
						$hostsAndIps[$parts[$i]] = $ip;
					}
				}
			}
		}

		return $this->remapHosts($hostsAndIps);
	}

	public function startCapture($captureWhat = BrowserMobProxy::CAPTURE_HEADERS)
	{
		$payload = [
			'captureHeaders' => (bool)($captureWhat & BrowserMobProxy::CAPTURE_HEADERS),
			'captureContent' => (bool)($captureWhat & BrowserMobProxy::CAPTURE_CONTENT),
			'captureBinaryContent' => (bool)($captureWhat & BrowserMobProxy::CAPTURE_BINARY_CONTENT)
		];

		return $this->request('PUT', $this->port . '/har', $payload);
	}
}