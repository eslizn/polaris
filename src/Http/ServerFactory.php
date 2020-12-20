<?php

namespace Polaris\Http;

use Polaris\Http\Exception\Exception;
use Polaris\Http\Server\Standard;
use Polaris\Http\Server\Swoole;

/**
 * Class Factory
 *
 * @package Polaris\Http
 */
class ServerFactory
{

	/**
	 * sapi mapping
	 *
	 * @var string[]
	 */
	protected static $adaptive =[
		'apache' => Standard::class,
		'apache2handler' => Standard::class,
		'cgi' => Standard::class,
		'cgi-fcgi' => Standard::class,
		'cli' => Swoole::class,
		'cli-server' => Standard::class,
		'embed' => Standard::class,
		'fpm-fcgi' => Standard::class,
		'litespeed' => Standard::class,
		'nsapi' => Standard::class,
		'phpdbg' => Standard::class,
	];

	/**
	 * @param mixed $class
	 * @param mixed ...$args
	 * @return Server
	 */
	public static function create($class, ...$args)
	{
		if (is_null($class) && isset(static::$adaptive[php_sapi_name()])) {
			$class = static::$adaptive[php_sapi_name()];
		}
		return new $class(...$args);
	}

}
