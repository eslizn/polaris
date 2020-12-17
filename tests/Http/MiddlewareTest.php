<?php

namespace Tests\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class MiddlewareTest
 * @package Tests\Http
 */
class MiddlewareTest extends TestCase
{

	/**
	 *
	 */
	public function testImplementation()
	{
		$response = $this->getResponseMock();
		$this->assertInstanceOf(RequestHandlerInterface::class, new Stack($response));
	}

	/**
	 *
	 */
	public function testServerMiddlewareStack()
	{
		$serverRequest = $this->getServerRequestMock();
		$response = $this->getResponseMock();

		$stack = new Stack($response, new DebugMiddleware("m1"), new DebugMiddleware("m2"), new DebugMiddleware("m3"));
		$stackResponse = $stack->handle($serverRequest);

		$this->assertInstanceOf(ResponseInterface::class, $stackResponse);
		$this->assertTrue($stackResponse->getBody()->getContents() === "m1.before\nm2.before\nm3.before\nm3.after\nm2.after\nm1.after\n");
	}

	/**
	 * @return MockObject|ResponseInterface
	 */
	private function getResponseMock()
	{
		return $this->getMockBuilder(ResponseInterface::class)
			->getMock();
	}

	/**
	 * @return MockObject|ServerRequestInterface
	 */
	private function getServerRequestMock()
	{
		return $this->getMockBuilder(ServerRequestInterface::class)
			->onlyMethods([
				'getServerParams',
				'getCookieParams',
				'withCookieParams',
				'getQueryParams',
				'withQueryParams',
				'getUploadedFiles',
				'withUploadedFiles',
				'getParsedBody',
				'withParsedBody',
				'getAttributes',
				'getAttribute',
				'withAttribute',
				'withoutAttribute',
				'getRequestTarget',
				'withRequestTarget',
				'getMethod',
				'withMethod',
				'getUri',
				'withUri',
				'getProtocolVersion',
				'withProtocolVersion',
				'getHeaders',
				'hasHeader',
				'getHeader',
				'getHeaderLine',
				'withHeader',
				'withAddedHeader',
				'withoutHeader',
				'getBody',
				'withBody',
			])->getMock();
	}

}