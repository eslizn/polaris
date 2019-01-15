<?php
namespace Polaris\Http\Middlewares;

use Polaris\Http\Exceptions\HttpException;
use Polaris\Http\Interfaces\RouterInterface;
use Polaris\Http\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class RouterMiddleware
 * @package Polaris\Http\Middlewares
 */
class RouterMiddleware implements RouterInterface, MiddlewareInterface
{

	use MiddlewareTrait;

	/**
	 * @var string
	 */
	protected $namespace;

	/**
	 * @var array
	 */
	protected $groupPatterns = [];

	/**
	 * @var array
	 */
	protected $groupMiddleware = [];

	/**
	 * @var \FastRoute\Dispatcher
	 */
	protected $dispatcher = null;

	/**
	 * @var array
	 */
	protected $routes = [];

	/**
	 * Router constructor.
	 * @param string $routes
	 * @param string $namespace
	 */
	public function __construct($routes, $namespace = 'App')
	{
		$this->namespace = $namespace;
		if (file_exists($routes)) {
			(function ($router) use ($routes) {
				include ($routes);
			})($this);
		}
		$this->dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) {
			foreach ($this->routes as $route) {
				$r->addRoute(...$route);
			}
		});
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function get($pattern, $handler, ...$middleware)
	{
		return $this->map(['GET'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function post($pattern, $handler, ...$middleware)
	{
		return $this->map(['POST'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function put($pattern, $handler, ...$middleware)
	{
		return $this->map(['PUT'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function delete($pattern, $handler, ...$middleware)
	{
		return $this->map(['DELETE'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function head($pattern, $handler, ...$middleware)
	{
		return $this->map(['HEAD'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function options($pattern, $handler, ...$middleware)
	{
		return $this->map(['OPTIONS'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function patch($pattern, $handler, ...$middleware)
	{
		return $this->map(['PATCH'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function trace($pattern, $handler, ...$middleware)
	{
		return $this->map(['TRACE'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function connect($pattern, $handler, ...$middleware)
	{
		return $this->map(['CONNECT'], $pattern, $handler, ...$middleware);
	}

	/**
	 * @param string $pattern
	 * @param mixed $handler
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function any($pattern, $handler, ...$middleware)
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
	public function map($methods, $pattern, $handler, ...$middleware)
	{
		$this->routes[] = [$methods, implode($this->groupPatterns) . $pattern, [$handler, array_merge($this->groupMiddleware, $middleware)]];
		return $this;
	}

	/**
	 * @param string $pattern
	 * @param \Closure $closure
	 * @param mixed ...$middleware
	 * @return static
	 */
	public function group($pattern, \Closure $closure, ...$middleware)
	{
		$this->groupPatterns[] = $pattern;
		$oldMiddleware = $this->groupMiddleware;
		$this->groupMiddleware = array_merge($this->groupMiddleware, $middleware);
		$closure($this);
		array_pop($this->groupPatterns);
		$this->groupMiddleware = $oldMiddleware;
		return $this;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$route = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
		switch ($route[0]) {
			case \FastRoute\Dispatcher::NOT_FOUND:
				throw new HttpException(404);
			case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				throw new HttpException(405);
			case \FastRoute\Dispatcher::FOUND:
				$request = $request->withQueryParams(array_merge($request->getQueryParams(), $route[2]));
				list($handler, $middlewares) = $route[1];
				if (is_string($handler) && !is_callable($handler)) {
					$handler = sprintf('%s\\Http\\Controllers\\%s', $this->namespace, $handler);
				}
				$dispatcher = new Dispatcher();
				$dispatcher->middlewares(...$middlewares)
					->middlewares(new Endpoint($handler));
				return $dispatcher->handle($request);
			default:
				throw new HttpException(500);
		}
	}

}