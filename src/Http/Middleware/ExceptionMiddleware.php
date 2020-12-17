<?php

namespace Polaris\Http\Middleware;

use Polaris\Http\Exception\HttpException;
use Polaris\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Class ExceptionMiddleware
 * @package Polaris\Middleware
 */
class ExceptionMiddleware implements MiddlewareInterface
{

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		} catch (HttpException $e) {
			return new Response($e->getStatusCode(), null, $e->getStatusText());
		} catch (Throwable $e) {
			return new Response(500, null, $e->__toString());
		}
	}

}