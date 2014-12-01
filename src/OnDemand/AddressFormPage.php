<?php

namespace PrestaShop\PSTAF\OnDemand;

class AddressFormPage extends OnDemandPage
{
	public function getFields()
	{
		return [
			'firstname' => ['selector' => '#inputFirstname'],
			'lastname' => ['selector' => '#inputLastname'],
			'company' => ['selector' => '#inputCompany'],
			'address' => ['selector' => '#inputAddress'],
			'address2' => ['selector' => '#inputAddressSecond'],
			'city' => ['selector' => '#inputCity'],
			'stateid' => ['selector' => '#inputState', 'method' => 'select'],
			'postcode' => ['selector' => '#inputPCode'],
			'countryid' => ['selector' => '#inputCountry', 'method' => 'select'],
			'phone' => ['selector' => '#inputHPhone'],
			'mobilephone' => ['selector' => '#inputMPhone'],
			'alias' => ['selector' => '#titleAddress'],
			'vatnumber' => ['selector' => '#inputVAT']
		];
	}

	public function __call($name, $args)
	{
		$m = [];
		if (preg_match('/^get(\w+)$/', $name, $m)) {
			$field = strtolower($m[1]);
			$fields = $this->getFields();
			if (isset($fields[$field])) {
				$spec = $fields[$field];
				$method = isset($spec['method']) ? $spec['method'] : 'default';
				if ($method === 'select') {
					return $this->getBrowser()->getSelectedValue($spec['selector']);
				} else {
					return $this->getBrowser()->getValue($spec['selector']);
				}
			}
		} elseif (preg_match('/^set(\w+)$/', $name, $m)) {
			$field = strtolower($m[1]);
			$fields = $this->getFields();
			if (isset($fields[$field])) {
				$spec = $fields[$field];
				$method = isset($spec['method']) ? $spec['method'] : 'default';
				if ($method === 'select') {
					$this->getBrowser()->select($spec['selector'], $args[0]);
				} else {
					$this->getBrowser()->fillIn($spec['selector'], $args[0]);
				}
				return $this;
			}
		}

		/**
		 * Emulate error if magic call failed.
		 */

		$class = get_class($this);
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        trigger_error("Call to undefined method $class::$name() in $file on line $line", E_USER_ERROR);
	}

	public function save()
	{
		$this->getBrowser()->click('button[type="submit"][value="change"]');

		if ($this->getBrowser()->hasVisible('.alert.alert-danger')) {
			throw new \PrestaShop\PSTAF\Exception\StandardErrorMessageDisplayedException('Could not save address.');
		}

		return $this;
	}
}