<?php

namespace Tests\Http;

use Polaris\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class DebugMiddleware
 * @package Tests\Http
 */
class DebugMiddleware implements MiddlewareInterface
{

	/**
	 * @var string
	 */
	private $tag;

	/**
	 * DebugMiddleware constructor.
	 * @param string $tag
	 */
	public function __construct($tag)
	{
		$this->tag = $tag;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$str = $this->tag . ".before\n";
		$response = $handler->handle($request);
		$str .= $response->getBody() ? $response->getBody()->getContents() : "";
		return new Response(200, null, $str . $this->tag . ".after\n");
	}

}