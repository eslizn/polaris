<?php

namespace Polaris\Socket\Traits;

/**
 * Trait FormatAddressTrait
 * @package Polaris\Socket\Traits
 */
trait FormatAddressTrait
{

	/**
	 * format given address/host/path and port
	 *
	 * @param string $address
	 * @param int    $port
	 * @return string
	 */
	protected function formatAddress($address, $port)
	{
		if ($port !== 0) {
			if (strpos($address, ':') !== false) {
				$address = '[' . $address . ']';
			}
			$address .= ':' . $port;
		}
		return $address;
	}

	/**
	 * format given address by splitting it into returned address and port set by reference
	 *
	 * @param string $address
	 * @param int $port
	 * @return string address with port removed
	 */
	protected function unformatAddress($address, &$port)
	{
		$colon = strrpos($address, ':');
		if ($colon !== false && (strpos($address, ':') === $colon || strpos($address, ']') === ($colon - 1))) {
			$port = (int)substr($address, $colon + 1);
			$address = substr($address, 0, $colon);
			if (substr($address, 0, 1) === '[') {
				$address = substr($address, 1, -1);
			}
		}
		return $address;
	}

}