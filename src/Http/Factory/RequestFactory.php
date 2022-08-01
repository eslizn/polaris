<?php

namespace Polaris\Http\Factory;

use Polaris\Http\Body;
use Polaris\Http\Cookies;
use Polaris\Http\Exception;
use Polaris\Http\Exception\InvalidArgumentException;
use Polaris\Http\Headers;
use Polaris\Http\Request;
use Polaris\Http\Stream;
use Polaris\Http\Uri;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 *
 */
class RequestFactory implements RequestFactoryInterface, ServerRequestFactoryInterface
{

	/**
	 *
	 * @param string $method
	 * @param UriInterface|string $uri
	 * @return RequestInterface
	 * @throws InvalidArgumentException
	 */
	public function createRequest(string $method, $uri): RequestInterface
	{
		return $this->createServerRequest($method, $uri);
	}

	/**
	 * @param string $method
	 * @param UriInterface|string $uri
	 * @param array $serverParams
	 * @return ServerRequestInterface
	 * @throws InvalidArgumentException
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
	{
		return new Request($method, is_string($uri) ? Uri::createFromString($uri) : $uri, null, [], $serverParams);
	}

	/**
	 * Create new HTTP request with data extracted from Swoole Request
	 *
	 * @param \Swoole\Http\Request $request
	 * @return RequestInterface
	 * @throws Exception
	 */
	public static function createFromSwoole(\Swoole\Http\Request $request): RequestInterface
	{
		if (empty($request) || !class_exists('\Swoole\Http\Request')) {
			throw new Exception('invalid request object', -__LINE__);
		}
		$method = $request->server['request_method'] ?? 'GET';
		$req = new Request(
			$method,
			UriFactory::createFromSwoole($request),
			Headers::createFromSwoole($request),
			new Cookies($request->cookie ?: []),
			array_change_key_case($request->server, CASE_UPPER),
			new Body($request->rawContent()),
			$request->files ? UploadedFileFactory::parseUploadedFiles($request->files) : []
		);
		if ($request->post) {
			$req = $req->withParsedBody($request->post);
		}

		return $req->withAttribute(\Swoole\Http\Request::class, $request);
	}

	/**
	 * Create new HTTP request with data extracted from the application
	 * Environment object
	 *
	 * @param array $globals The global server variables.
	 *
	 * @return RequestInterface
	 * @throws Exception
	 */
	public static function createFromGlobals(array $globals): RequestInterface
	{
		$method = $globals['REQUEST_METHOD'] ?? null;
		$uri = UriFactory::createFromGlobals($globals);
		$headers = Headers::createFromGlobals($globals);
		$cookies = Cookies::parse(current($headers['Cookie']??[]));
		$uploadedFiles = UploadedFileFactory::parseUploadedFiles($_FILES);
		$request = new Request($method, $uri, $headers, $cookies, $globals, new Body(), $uploadedFiles);

		if (is_array($_POST) && $_POST) {
			$request = $request->withParsedBody($_POST);
		} else if ($headers['Content-Length']) {
			$request = $request->withBody(new Stream(STDIN));
		}
		return $request;
	}

}