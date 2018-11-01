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
 * Headers Interface
 *
 * @package Slim
 * @since   1.0.0
 */
interface HeadersInterface extends CollectionInterface
{

	/**
	 * @param $key
	 * @param $value
	 * @return mixed
	 */
    public function add($key, $value);

	/**
	 * @param $key
	 * @return mixed
	 */
    public function normalizeKey($key);


}
