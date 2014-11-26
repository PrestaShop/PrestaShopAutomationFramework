<?php

namespace PrestaShop\PSTAF\Helper;

class HumanHash
{
	public static function shortenMd5($input)
	{	
		static $decimal = [
			'0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
			'6' => 6, '7' => 7, '8' => 8, '9' => 9, 'a' => 10, 'b' => 11,
			'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15
		];

		static $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

		$input = strtolower($input);
		$output = '';

		for ($pos = 0; $pos < strlen($input) - 1; $pos += 2) {
			$hex = substr($input, $pos, 2);
			$dec = 16 * $decimal[$hex[0]] + $decimal[$hex[1]];

			$out = '';
			if ($dec < strlen($alphabet)) {
				$out .= $alphabet[$dec];
			} else {
				$rem = $dec % strlen($alphabet);
				$div = ($dec - $rem) / strlen($alphabet);
				$out .= $alphabet[$div].$alphabet[$rem];
			}

			$output .= $out;
		}
		return $output;
	}

	public static function humanMd5($input)
	{
		return static::shortenMd5(md5(is_scalar($input) ? $input : serialize($input)));
	}
}