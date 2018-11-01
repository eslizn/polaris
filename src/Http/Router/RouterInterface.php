<?php
namespace Polaris\Http\Router;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * Interface RouterInterface
 * @package Polaris\Http\Router
 */
interface RouterInterface extends RequestHandlerInterface
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

}