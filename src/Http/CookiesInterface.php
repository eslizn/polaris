<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim-Http
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim-Http/blob/master/LICENSE (MIT License)
 */
namespace Polaris\Http;

defined('HTTP_COOKIE_PARSE_RAW') or define('HTTP_COOKIE_PARSE_RAW', 1);
defined('HTTP_COOKIE_SECURE ') or define('HTTP_COOKIE_SECURE ', 16);
defined('HTTP_COOKIE_HTTPONLY') or define('HTTP_COOKIE_HTTPONLY', 32);

/**
 * Cookies Interface
 *
 * @package Slim
 * @since   1.0.0
 */
interface CookiesInterface
{
	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
    public function get($name, $default = null);

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
    public function set($name, $value);

	/**
	 * @param string $name
	 * @param array $properties
	 * @return mixed
	 */
    public static function build($name, $properties = []);

	/**
	 * @param string $cookies
	 * @return array
	 */
    public static function parse($cookies, $flags = 0, $extras = []);
}
