<?php
namespace Polaris\Http\Middlewares;

use Polaris\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Trait MiddlewareTrait
 * @package Polaris\Http\Middlewares
 */
trait MiddlewareTrait
{

	/**
	 * @var MiddlewareInterface[]
	 */
	private $stack = [];

	/**
	 * @param mixed ...$middlewares
	 * @return static
	 */
	public function middlewares(...$middlewares)
	{
		foreach ($middlewares as $middleware) {
			array_unshift($this->stack, $middleware);
		}
		return $this;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		if (empty($this->stack)) {
			throw new \RuntimeException('middleware is empty!', -__LINE__);
		}
		$delegate = new class implements RequestHandlerInterface {
			public function handle(ServerRequestInterface $request): ResponseInterface {
				return new Response();
			}
		};
		foreach ($this->stack as $middleware) {
			if (is_string($middleware) && class_exists($middleware)) {
				$middleware = new $middleware;
			}
			if (!($middleware instanceof MiddlewareInterface)) {
				throw new \InvalidArgumentException('invalid middleware!', -__LINE__);
			}
			$delegate = new Delegate($middleware, $delegate);
		}
		return $delegate->handle($request);
	}

}