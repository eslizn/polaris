<?php

namespace Polaris\Http\Router;

use Closure;

/**
 * RouteTrait
 *
 * @package Polaris\Http\Router
 * @author eslizn <eslizn@gmail.com>
 */
trait RouteTrait
{

	/**
	 * @var array
	 */
	protected array $routes = [];

	/**
	 * @var array
	 */
	protected array $paths = [];

	/**
	 * @var array
	 */
	protected array $middleware = [];

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function get(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['GET'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function post(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['POST'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function put(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['PUT'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function delete(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['DELETE'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function head(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['HEAD'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function options(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['OPTIONS'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function patch(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['PATCH'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function trace(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['TRACE'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function connect(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['CONNECT'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function any(string $pattern, $handler, ...$middleware): self
	{
		return $this->map(['*'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param mixed $methods
	 * @param string $pattern
	 * @param mixed handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function map($methods, string $pattern, $handler, ...$middleware): self
	{
		$this->routes[] = [$methods, implode($this->paths) . $pattern, [$handler, array_merge($this->middleware, $middleware)]];
		return $this;
	}

	/**
	 * @param string $pattern
	 * @param Closure $closure
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function group(string $pattern, Closure $closure, ...$middleware): self
	{
		$this->paths[] = $pattern;
		$this->middleware = array_merge($this->middleware, $middleware);
		$closure($this);
		array_pop($this->paths);
		$this->middleware = array_slice($this->middleware, 0, -sizeof($middleware));
		return $this;
	}

}
