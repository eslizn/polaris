<?php
namespace Polaris\Http\Middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Delegate
 * @package Polaris\Http\Middlewares
 */
class Delegate implements RequestHandlerInterface
{

	/**
	 * @var MiddlewareInterface
	 */
	protected $middleware;

	/**
	 * @var RequestHandlerInterface
	 */
	protected $next;

	/**
	 * Delegate constructor.
	 * @param MiddlewareInterface $middleware
	 * @param RequestHandlerInterface $next
	 */
	public function __construct(MiddlewareInterface $middleware, RequestHandlerInterface $next)
	{
		$this->middleware = $middleware;
		$this->next = $next;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->middleware->process($request, $this->next);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function __invoke(ServerRequestInterface $request): ResponseInterface
	{
		return $this->handle($request);
	}

}