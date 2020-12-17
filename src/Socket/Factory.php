<?php

namespace Polaris\Socket;

use Polaris\Socket\Exception\InvalidArgumentException;

/**
 * Class Factory
 * @package Polaris\Socket
 */
class Factory
{

	/**
	 * @var array
	 */
	protected static $schemes = [
		'tcp'	=> [AF_INET, SOCK_STREAM, SOL_TCP],
		'tcp6'	=> [AF_INET6, SOCK_STREAM, SOL_TCP],
		'udp'	=> [AF_INET, SOCK_DGRAM, SOL_UDP],
		'udp6'	=> [AF_INET6, SOCK_DGRAM, SOL_UDP],
		//'icmp'	=> [AF_INET, SOCK_RAW, getprotobyname('icmp')],
		//'icmp6'	=> [AF_INET6, SOCK_RAW, getprotobyname('ipv6-icmp')],
		'unix'	=> [AF_UNIX, SOCK_STREAM, 0],
		'udg'	=> [AF_UNIX, SOCK_DGRAM, 0],
	];

	/**
	 * @param string $address
	 * @return Socket
	 * @throws \Throwable
	 */
	public static function createServer($address)
	{
		$socket = static::createFromScheme($address);
		try {
			$socket->bind($address);
			if (in_array($socket->getType(), [SOCK_STREAM, SOCK_SEQPACKET])) {
				$socket->listen();
			}
			return $socket;
		} catch (\Throwable $e) {
			$socket->close();
			throw $e;
		}
	}

	/**
	 * @param string $address
	 * @return Socket
	 * @throws \Throwable
	 */
	public static function createClient($address)
	{
		$socket = static::createFromScheme($address);
		try {
			return $socket->connect($address);
		} catch (\Throwable $e) {
			$socket->close();
			throw $e;
		}
	}

	/**
	 * @param string $address
	 * @return Socket
	 * @throws InvalidArgumentException
	 */
	public static function createFromScheme(&$address)
	{
		$parsed = parse_url($address);
		if (!isset($parsed['scheme']) || !isset(static::$schemes[$parsed['scheme']])) {
			throw new InvalidArgumentException('unsupported scheme ' . $parsed['scheme'], -__LINE__);
		}
		$address = isset($parsed['host']) ? $parsed['host'] : '';
		$address .= isset($parsed['port']) ? ':' . $parsed['port'] : '';
		$class = static::getSocketClass();
		$object = new $class();
		return $object->create(...static::$schemes[$parsed['scheme']]);
	}

	/**
	 * check is coroutine env
	 *
	 * @return bool
	 */
	protected static function inCoroutine()
	{
		return class_exists('\Swoole\Coroutine')
			&& \Swoole\Coroutine::getuid() >= 0;
	}

	/**
	 * auto detector driver
	 *
	 * @return string
	 */
	protected static function getSocketClass()
	{
		return sprintf('\\%s\\Drivers\\%s', __NAMESPACE__, static::inCoroutine() ? 'Swoole' : 'Standard');
	}

}