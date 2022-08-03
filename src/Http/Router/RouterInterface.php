<?php

namespace Polaris\Http\Router;

/**
 * RouterInterface
 *
 * @package Polaris\Http\Router
 * @author eslizn <eslizn@gmail.com>
 */
interface RouterInterface
{

	/**
	 * @param mixed $methods
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function map($methods, string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param \Closure $closure
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function group(string $pattern, \Closure $closure, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function get(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function post(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function put(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function delete(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function head(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function options(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function patch(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function trace(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function connect(string $pattern, $handler, ...$middleware): self;
	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return mixed
	 */
	public function any(string $pattern, $handler, ...$middleware): self;

}