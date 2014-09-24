<?php

namespace PrestaShop\ShopCapability;

class CheckoutManagement extends ShopCapability
{
	/**
	 * Order the current cart.
	 *
	 * Supposes the user is logged in, and the browser is currently on a FO page.
	 * 
	 * options must contain:
	 * - carrier
	 * - payment (only bankwire for now)
	 * 
	 * @return array with at least order_id and total_cart set
	 */
	public function orderCurrentCartFiveSteps(array $options)
	{
		$browser = $this->getBrowser();
		$shop = $this->getShop();

		$browser
		->visit($shop->getFrontOfficeURL())
		->click('div.shopping_cart a')
		->click('a.standard-checkout')
		->clickButtonNamed('processAddress')
		->clickLabelFor('cgv')
		->click('{xpath}//tr[contains(., "'.$options['carrier'].'")]//input[@type="radio"]')
		->clickButtonNamed('processCarrier');

		$cart_total = $browser->getAttribute('#total_price', 'data-selenium-total-price');

		if ($options['payment'] !== 'bankwire')
			throw new \Exception('Sorry, only payment with Bankwire is allowed for now.');

		$browser
		->click('a.bankwire')
		->click('#center_column button[type="submit"]');

		$id_order = (int)$browser->getURLParameter('id_order');
		
		if ($id_order <= 0)
			throw new \Exception('Could not create order: no valid id_order found after payment.');

		return [
			'id_order' => $id_order,
			'cart_total' => $cart_total
		];
	}
}