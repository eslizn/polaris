<?php

namespace Polaris\Http\Middleware;

use Polaris\Http\Exception\HttpException;
use Polaris\Http\Headers;
use Polaris\Http\Request;
use Polaris\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionFunctionAbstract;
use ReflectionClass;
use ReflectionFunction;
use ReflectionException;

/**
 * Class InvokeMiddleware
 * @package Polaris\Http\Middlewares
 */
class InvokeMiddleware implements MiddlewareInterface
{

	/**
	 * @var mixed
	 */
	protected $callable;

	/**
	 * InvokeMiddleware constructor.
	 * @param mixed $callable
	 */
	public function __construct($callable)
	{
		$this->callable = $callable;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 * @throws ReflectionException|HttpException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$response = null;
		if (is_string($this->callable) && !is_callable($this->callable)) {
			list($class, $method) = explode('@', $this->callable);
			if (!class_exists($class)) {
				throw new HttpException(404);
			}
			$reflect = new ReflectionClass($class);
			$arguments = $reflect->getConstructor() ? static::parseArguments($reflect->getConstructor(), $request) : [];
			$class = new $class(...$arguments);
			if (!$reflect->hasMethod($method)) {
				throw new HttpException(404);
			}
			$arguments = static::parseArguments($reflect->getMethod($method), $request);
			$response = $class->$method(...$arguments);
		} else {
			if (!is_callable($this->callable)) {
				throw new HttpException(500);
			}
			$arguments = static::parseArguments(new ReflectionFunction($this->callable), $request);
			$response = ($this->callable)(...$arguments);
		}
		if (is_null($response)) {
			return new Response();
		} else if (is_scalar($response)) {
			return new Response(200, new Headers(['Content-Type' => 'text/plain']), $response);
		} else if (is_array($response) || (is_object($response) && $response instanceof \JsonSerializable)) {
			return (new Response)->withJson($response);
		} else {
			return $response;
		}
	}

	/**
	 * @param ReflectionFunctionAbstract $abstract
	 * @param ServerRequestInterface $request
	 * @return array
	 * @throws ReflectionException
	 */
	protected static function parseArguments(ReflectionFunctionAbstract $abstract, ServerRequestInterface $request)
	{
		$arguments = [];
		foreach ($abstract->getParameters() as $p) {
			if ($p->getClass()) {
				$arguments[] = in_array($p->getClass()->getName(), [
					Request::class,
					ServerRequestInterface::class
				]) ? $request : $request->getAttribute($p->getClass()->getName());
			} else if (!is_null($request->getAttribute($p->getName()))) {
				$arguments[] = $request->getAttribute($p->getName());
			} else if (key_exists($p->getName(), $request->getParsedBody()?:[])) {
				$arguments[] = $request->getParsedBody()[$p->getName()];
			} else if (key_exists($p->getName(), $request->getQueryParams())) {
				$arguments[] = $request->getQueryParams()[$p->getName()];
			} else {
				$arguments[] = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null;
			}
		}
		return $arguments;
	}

}