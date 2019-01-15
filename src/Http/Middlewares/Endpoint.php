<?php
namespace Polaris\Http\Middlewares;

use Polaris\Http\Exceptions\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Endpoint
 * @package Polaris\Http\Middlewares
 */
class Endpoint implements RequestHandlerInterface, MiddlewareInterface
{

	/**
	 * @var mixed
	 */
	protected $callable;

	/**
	 * CallableMiddleware constructor.
	 * @param string $callable
	 */
	public function __construct($callable)
	{
		$this->callable = $callable;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		return $this->handle($request);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$response = null;
		if (is_string($this->callable) && !is_callable($this->callable)) {
			if (class_exists($callable)) {
				$callable = new $callable;
			}
			list($class, $method) = explode('@', $handler);
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
			$response = $class->$method(...$arguments);
		} else {
			if (!is_callable($handler)) {
				throw new HttpException(500);
			}
			$arguments = static::parseArguments(new \ReflectionFunction($handler), $request);
			$response = $handler(...$arguments);
		}
		if (is_null($response)) {
			return new Response();
		} else if (is_scalar($response)) {
			return new Response(200, ['Content-Type' => 'text/plain'], $response);
		} else if (is_array($response) || (is_object($response) && $response instanceof \JsonSerializable)) {
			return new Response(200, ['Content-Type' => 'application/json'], json_encode($response));
		} else {
			return $response;
		}
	}

	/**
	 * @param \ReflectionFunctionAbstract $abstract
	 * @param ServerRequestInterface $request
	 * @return array
	 */
	protected static function parseArguments(\ReflectionFunctionAbstract $abstract, ServerRequestInterface $request)
	{
		$arguments = [];
		foreach ($abstract->getParameters() as $p) {
			if ($p->getClass()) {
				$arguments[] = $p->getClass()->getName() === ServerRequestInterface::class ? $request : $request->getAttribute($p->getClass()->getName());
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