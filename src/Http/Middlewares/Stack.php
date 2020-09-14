<?php

namespace Polaris\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;

/**
 * Class Stack
 * @package Polaris\Http\Middlewares
 */
class Stack implements RequestHandlerInterface
{

	/**
	 * @var MiddlewareInterface[]
	 */
	protected $middlewares = [];

	/**
	 * @var ResponseInterface
	 */
	protected $response;

	/**
	 * Stack constructor.
	 * @param ResponseInterface $response
	 * @param mixed ...$middlewares
	 */
	public function __construct(ResponseInterface $response, ...$middlewares)
	{
		$this->response = $response;
		$this->middlewares = $middlewares;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return sizeof($this->middlewares) ?
			static::normal($this->middlewares[0])->process($request, $this->next()) :
			$this->response;
	}

	/**
	 * @return RequestHandlerInterface
	 */
	private function next(): RequestHandlerInterface
	{
		return new static($this->response, ...(array_slice($this->middlewares, 1) ?: []));
	}

	/**
	 * @param mixed $middleware
	 * @return MiddlewareInterface
	 */
	private static function normal($middleware): MiddlewareInterface {
		if (is_string($middleware)) {
			if (!class_exists($middleware)) {
				throw new InvalidArgumentException(sprintf('invalid middleware: %s', $middleware), -__LINE__);
			}
			$middleware = new $middleware();
		}
		if (!is_object($middleware)) {
			throw new InvalidArgumentException('middleware must object', -__LINE__);
		}
		if (!($middleware instanceof MiddlewareInterface)) {
			throw new InvalidArgumentException(sprintf('middleware: %s must instanceof MiddlewareInterface', get_class($middleware)), -__LINE__);
		}
		return $middleware;
	}

}