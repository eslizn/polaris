<?php

namespace Polaris\Http;

/**
 * Class Factory
 *
 * @package Polaris\Http
 */
class Factory
{

	/**
	 * @param mixed ...$args
	 * @return Server
	 */
	public static function createServer(...$args)
	{
		$class = static::getServerClass();
		return new $class(...$args);
	}

	/**
	 * auto server driver
	 *
	 * @return string
	 */
	protected static function getServerClass()
	{
		return sprintf('\\%s\\Server\\%s', __NAMESPACE__, strcasecmp(php_sapi_name(), 'cli') ? 'Standard' : 'Swoole');
	}

}
