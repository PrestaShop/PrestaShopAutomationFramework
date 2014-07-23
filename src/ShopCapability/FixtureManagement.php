<?php

namespace PrestaShop\ShopCapability;

class FixtureManagement extends ShopCapability
{
	public function setupInitialState(array $initial_state)
	{
		if (isset($initial_state['ShopInstallation']))
			$this->getShop()->getInstaller()->install($initial_state['ShopInstallation']);

		return $this;
	}
}