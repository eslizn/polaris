<?php

namespace Polaris\Socket;

use Throwable;

/**
 * Class ClientFactory
 * @package Polaris\Socket
 */
class ClientFactory extends Factory
{

	/**
	 * @param mixed $address
	 * @param null $driver
	 * @return Socket
	 * @throws Exception\InvalidArgumentException
	 * @throws Exception\SocketException
	 * @throws Throwable
	 */
	public static function create($address, $driver = null)
	{
		$socket = static::createFromScheme($address, $driver);
		try {
			return $socket->connect($address);
		} catch (Throwable $e) {
			$socket->close();
			throw $e;
		}
	}

}