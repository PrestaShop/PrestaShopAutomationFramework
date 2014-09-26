<?php

namespace PrestaShop\ShopCapability;

class OrderManagement extends ShopCapability
{
	public function visit($id_order)
	{
		$this
		->getShop()
		->getBackOfficeNavigator()
		->visit('AdminOrders', 'view', $id_order);

		return $this;
	}

	public function validate()
	{
		$this
		->getBrowser()
		->jqcSelect('#id_order_state', 2)
		->clickButtonNamed('submitState');

		return $this;
	}

	public function getInvoiceFromJSON()
	{
		$browser = $this->getBrowser();

		$invoice_json_link = $browser->getAttribute('[data-selenium-id="view_invoice"]', 'href').'&debug=1';
		$browser->visit($invoice_json_link);
		$json = json_decode($browser->find('body')->getText(), true);

		if (!$json)
			throw new \Exception('Invalid invoice JSON found');

		return $json;
	}
}