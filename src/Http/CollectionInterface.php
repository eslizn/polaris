<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim-Http
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim-Http/blob/master/LICENSE (MIT License)
 */
namespace Polaris\Http;

/**
 * Interface CollectionInterface
 * @package Polaris\Http
 */
interface CollectionInterface extends \ArrayAccess, \Countable, \IteratorAggregate
{

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return static
	 */
	public function set($key, $value);

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null);

	/**
	 * @param array $items
	 * @return mixed
	 */
	public function replace(array $items);

	/**
	 * @return mixed
	 */
	public function all();

	/**
	 * @param string $key
	 * @return boolean
	 */
	public function has($key);

	/**
	 * @param string $key
	 * @return static
	 */
	public function remove($key);

	/**
	 * @return static
	 */
	public function clear();

}
