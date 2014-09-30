<?php

namespace PrestaShop\ShopCapability;

class CategoryManagement extends ShopCapability
{
	/**
	 * Create a category
	 * @param  array  $options the characteristics of the category:
	 * - name
	 * - displayed
	 * - parent (int id)
	 * - description
	 * - friendly_url
	 * @return int the id of the created category
	 */
	public function createCategory(array $options)
	{
		$browser = $this->getShop()->getBackOfficeNavigator()->visit('AdminCategories', 'new');

		if (isset($options['name']))
			$browser->fillIn($this->i18nFieldName('#name'), $options['name']);

		if (isset($options['displayed']))
			$browser->prestaShopSwitch('active', $options['displayed']);

		if (isset($options['parent']))
		{
			$browser->click('#expand-all-categories-tree');
			$browser->click('#categories-tree input[name="id_parent"][value="'.$options['parent'].'"]');
		}

		if (isset($options['friendly_url']))
			$browser->fillIn($this->i18nFieldName('#link_rewrite'), $options['friendly_url']);

		$browser->click('#category_form_submit_btn');

		$this->getShop()->expectStandardSuccessMessage();

		$paginator = $this
		->getShop()
		->getBackOfficePaginator()
		->getPaginatorFor('AdminCategories');

		foreach ($paginator->scrapeAll() as $row)
			if (trim($row['name']) === $options['name'] && (int)$row['id'] > 0)
				return (int)$row['id'];

		throw new \PrestaShop\Exception\FailedTestException('Could not find ID of created category.');
	}
}