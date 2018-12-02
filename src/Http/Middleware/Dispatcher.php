<?php
namespace Polaris\Http\Middleware;

use Polaris\Http\Exceptions\HttpException;
use Polaris\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Dispatcher
 * @package Polaris\Http\Middleware
 */
class Dispatcher extends \SplQueue implements RequestHandlerInterface
{

	/**
	 * @param \ReflectionFunctionAbstract $abstract
	 * @param ServerRequestInterface $request
	 * @return array
	 */
	private static function parseArguments(\ReflectionFunctionAbstract $abstract, ServerRequestInterface $request)
	{
		$arguments = [];
		foreach ($abstract->getParameters() as $p) {
			if ($p->getClass()) {
				$arguments[] = $request->getAttribute($p->getClass()->getName());
			} else if (!is_null($request->getAttribute($p->getName()))) {
				$arguments[] = $request->getAttribute($p->getName());
			} else if (key_exists($p->getName(), $request->getParsedBody())) {
				$arguments[] = $request->getParsedBody()[$p->getName()];
			} else if (key_exists($p->getName(), $request->getQueryParams())) {
				$arguments[] = $request->getQueryParams()[$p->getName()];
			} else {
				$arguments[] = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null;
			}
		}
		return $arguments;
	}

	/**
	 * @param mixed ...$value
	 * @return static
	 */
	public function enqueue(...$list)
	{
		foreach ($list as $item) {
			parent::enqueue($item);
		}
		return $this;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->resolve()->handle($request);
	}

	/**
	 * @return RequestHandlerInterface
	 */
	private function resolve(): RequestHandlerInterface
	{
		return new CallableHandler(function (ServerRequestInterface $request) {
			if ($this->isEmpty()) {
				return $request->getAttribute(ResponseInterface::class);
			}
			$middleware = $this->dequeue();
			if (is_string($middleware)) {
				if (strpos($middleware, '@') !== false) {
					$middleware = new CallableHandler(function (ServerRequestInterface $request) use ($middleware) {
						list($class, $method) = explode('@', $middleware);
						if (!class_exists($class)) {
							throw new HttpException(404);
						}
						$reflect = new \ReflectionClass($class);
						$arguments = $reflect->getConstructor() ? static::parseArguments($reflect->getConstructor(), $request) : [];
						$class = new $class(...$arguments);
						if (!$reflect->hasMethod($method)) {
							throw new HttpException(404);
						}
						$arguments = static::parseArguments($reflect->getMethod($method), $request);
						return $class->$method(...$arguments);
					});
				} else if (is_subclass_of($middleware, MiddlewareInterface::class)) {
					$reflect = new \ReflectionClass($middleware);
					$arguments = $reflect->getConstructor() ? static::parseArguments($reflect->getConstructor(), $request) : [];
					$middleware = new $middleware(...$arguments);
				}
			} else if (is_callable($middleware)) {
				$middleware = new CallableHandler($middleware);
			}
			if (!($middleware instanceof MiddlewareInterface)) {
				throw new \UnexpectedValueException('invalid middleware!', -__LINE__);
			}
			return $middleware->process($request->withAttribute(Request::class, $request), $this->resolve());
		});
	}

}