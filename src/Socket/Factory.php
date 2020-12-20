<?php

namespace Polaris\Socket;

use Polaris\Socket\Driver\Standard;
use Polaris\Socket\Driver\Swoole;
use Polaris\Socket\Exception\InvalidArgumentException;
use Throwable;

/**
 * Class Factory
 * @package Polaris\Socket
 */
abstract class Factory
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
	 * @param mixed $address
	 * @param mixed $driver
	 * @return Socket
	 */
	abstract public static function create($address, $driver = null);

	/**
	 * @param mixed $address
	 * @param mixed $driver
	 * @return Socket
	 * @throws Exception\SocketException
	 * @throws InvalidArgumentException
	 */
	protected static function createFromScheme(&$address, $driver = null)
	{
		if (is_null($driver)) {
			$driver = class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() ? Swoole::class : Standard::class;
		}
		$parsed = parse_url($address);
		if (!isset($parsed['scheme']) || !isset(static::$schemes[$parsed['scheme']])) {
			throw new InvalidArgumentException('unsupported scheme ' . $parsed['scheme'], -__LINE__);
		}
		$address = isset($parsed['host']) ? $parsed['host'] : '';
		$address .= isset($parsed['port']) ? ':' . $parsed['port'] : '';
		$object = new $driver();
		return $object->create(...static::$schemes[$parsed['scheme']]);
	}

}