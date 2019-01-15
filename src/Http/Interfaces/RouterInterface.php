<?php
namespace Polaris\Http\Interfaces;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface RouterInterface
 *
 * @package Polaris\Http\Interfaces
 */
interface RouterInterface
{
	/**
	 * @param mixed $methods
	 * @param string $pattern
	 * @param mixed handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function map($methods, $pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param \Closure $closure
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function group($pattern, \Closure $closure, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function get($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function post($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function put($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function delete($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function head($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function options($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function patch($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function trace($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function connect($pattern, $handler, ...$middleware);
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function any($pattern, $handler, ...$middleware);
	
}