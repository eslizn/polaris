<?php

namespace Polaris\Socket;

use Throwable;

/**
 * Class ServerFactory
 * @package Polaris\Socket
 */
class ServerFactory extends Factory
{

	/**
	 * @param mixed $address
	 * @param null $driver
	 * @return Socket
	 * @throws Exception\InvalidArgumentException
	 * @throws Throwable
	 */
	public static function create($address, $driver = null)
	{
		$socket = static::createFromScheme($address, $driver);
		try {
			$socket->bind($address);
			if (in_array($socket->getType(), [SOCK_STREAM, SOCK_SEQPACKET])) {
				$socket->listen();
			}
			return $socket;
		} catch (Throwable $e) {
			$socket->close();
			throw $e;
		}
	}

}