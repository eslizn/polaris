<?php
namespace Polaris\Http\Middleware;

use Polaris\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CallableHandler
 * @package Polaris\Http\Middleware
 */
class CallableHandler implements MiddlewareInterface, RequestHandlerInterface
{

	/**
	 * @var callable
	 */
	protected $callable;

	/**
	 * CallableHandler constructor.
	 *
	 * @param mixed $callable
	 */
	public function __construct(callable $callable)
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
		$response = $this->handle($request);
		return $handler->handle(!is_null($response) && ($response instanceof ResponseInterface) ?
			$request->withAttribute(ResponseInterface::class, $response) : $request);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$response = $this->__invoke($request);
		if (!($response instanceof ResponseInterface)) {
			if (is_null($response)) {
				$response = new Response();
			} else if (is_scalar($response)) {
				$response = new Response(200, null, $response);
			} else if (is_object($response)) {
				$response = new Response\JsonResponse($response instanceof \JsonSerializable ? $response->jsonSerialize() : get_class_vars($response));
			} else {
				$response = new Response\JsonResponse($response);
			}
		}
		return $response;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return mixed
	 */
	public function __invoke(ServerRequestInterface $request)
	{
		return ($this->callable)($request);
	}

}