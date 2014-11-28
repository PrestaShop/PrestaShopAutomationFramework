<?php

namespace PrestaShop\PSTAF\PageObject;

class EditAddress extends PageObject
{
	public function getFields()
	{
		return [
			'firstname' => ['selector' => '#firstname'],
			'lastname' => ['selector' => '#lastname'],
			'company' => ['selector' => '#company'],
			'address' => ['selector' => '#address1'],
			'address2' => ['selector' => '#address2'],
			'city' => ['selector' => '#city'],
			'stateid' => ['selector' => '#id_state', 'method' => 'select'],
			'postcode' => ['selector' => '#postcode'],
			'countryid' => ['selector' => '#id_country', 'method' => 'select'],
			'phone' => ['selector' => '#phone'],
			'mobilephone' => ['selector' => '#phone_mobile'],
			'additionalinformation' => ['selector' => '#other'],
			'alias' => ['selector' => '#alias']
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
		$this->getBrowser()->click('#submitAddress');

		if ($this->getBrowser()->hasVisible('.alert.alert-danger')) {
			throw new \PrestaShop\PSTAF\Exception\StandardErrorMessageDisplayedException('Could not save address.');
		}

		return $this;
	}
}
