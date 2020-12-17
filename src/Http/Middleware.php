<?php

namespace Polaris\Http;

use Polaris\Http\Exceptions\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Middleware
 * @package Polaris\Http
 */
class Middleware implements RequestHandlerInterface
{

	/**
	 * @var MiddlewareInterface[]
	 */
	protected $middleware = [];

	/**
	 * @var ResponseInterface
	 */
	protected $response;

	/**
	 * Middleware constructor.
	 * @param ResponseInterface $response
	 * @param mixed ...$middleware
	 */
	public function __construct(ResponseInterface $response, ...$middleware)
	{
		$this->response = $response;
		$this->middleware = $middleware;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws InvalidArgumentException
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return sizeof($this->middleware) ?
			static::normal($this->middleware[0])->process($request, $this->next()) :
			$this->response;
	}

	/**
	 * @return RequestHandlerInterface
	 */
	private function next(): RequestHandlerInterface
	{
		return new static($this->response, ...(array_slice($this->middleware, 1) ?: []));
	}

	/**
	 * @param mixed $middleware
	 * @return MiddlewareInterface
	 * @throws InvalidArgumentException
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