<?php

namespace PrestaShop\ShopCapability\Helper;

class BackOfficePaginator
{
	private $pagination;
	private $settings;

	public function __construct(
		\PrestaShop\ShopCapability\BackOfficePagination $pagination,
		array $settings
	)
	{
		$this->pagination = $pagination;
		$this->settings = $settings;
	}

	public function getCurrentPageNumber()
	{
		$browser = $this->pagination->getShop()->getBrowser();
		return (int)$browser->getAttribute($this->settings['container_selector'].' ul.pagination li.active a', 'data-page');
	}

	public function getNextPageNumber()
	{
		$next = $this->getCurrentPageNumber() + 1;
		try {
			$this->pagination->getShop()->getBrowser()
			->find($this->settings['container_selector'].' ul.pagination li a[data-page="'.$next.'"]');
			return $next;
		} catch (\Exception $e)
		{
			return false;
		}
	}

	public function getLastPageNumber()
	{
		return (int)$this->pagination->getShop()
				->getBrowser()->getAttribute(
					$this->settings['container_selector'].' ul.pagination li:last-child a', 'data-page'
				);
	}

	public function gotoPage($n)
	{
		$this->pagination->getShop()->getBrowser()
		->click($this->settings['container_selector'].' ul.pagination li a[data-page="'.$n.'"]');
	}

	private function getHeaderName($header)
	{
		if (is_string($header))
			return $header;
		else
			return $header['name'];
	}

	private function getTDValue($header, $td)
	{
		$type = is_string($header) ? 'verbatim' : $header['type'];
		$m = [];

		if ($type === 'verbatim')
		{
			return $td->getText();
		}
		elseif (preg_match('/^switch:(.+)$/', $type, $m))
		{
			return $td->findElement(\WebDriverBy::cssSelector('.'.$m[1]))->isDisplayed();
		}
		elseif (preg_match('/^i18n:(.+)$/', $type, $m))
		{
			$value = $this->pagination->i18nParse($td->getText(), $m[1]);
			return $value;
		}
		else
		{
			return null;
		}
	}

	public function scrape()
	{
		$rows = [];

		foreach ($this->pagination->getShop()->getBrowser()->find(
			$this->settings['container_selector'].' '.$this->settings['table_selector'].' tbody tr',
			['unique' => false]
		) as $tr)
		{
			$row = [];
			foreach ($tr->findElements(\WebDriverBy::cssSelector('td')) as $n => $td)
			{
				if (!empty($this->settings['columns'][$n]))
				{
					$header = $this->settings['columns'][$n];
					$row[$this->getHeaderName($header)] = $this->getTDValue($header, $td);
				}
			}
			$rows[] = $row;
		}

		return $rows;
	}

	public function scrapeAll()
	{
		try {
			$max_page = $this->getLastPageNumber();
		} catch (\Exception $e) {
			$max_page = null;
		}

		if ($max_page === null)
			return $this->scrape();

		$rows = [];
		for($p = 1; $p <= $max_page; $p++)
		{
			$this->gotoPage($p);
			$data = $this->scrape();
			$rows = array_merge($rows, $data);
		}
		return $rows;
	}

}
