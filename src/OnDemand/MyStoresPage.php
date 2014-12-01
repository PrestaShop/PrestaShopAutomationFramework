<?php

namespace PrestaShop\PSTAF\OnDemand;

class MyStoresPage extends OnDemandPage
{
	public function gotoDomains()
	{
		$this->getBrowser()->click('{xpath}//a[contains(@href, "init=select_domain")]');

		return new DomainsPage($this->getBrowser(), $this->getSecrets());
	}
}