<?php

namespace Polaris\Http\Factory;

use Polaris\Http\Exception;
use Polaris\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class ResponseFactory implements ResponseFactoryInterface
{

	/**
	 * @param int $code
	 * @param string $reasonPhrase
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
	{
		return (new Response())->withStatus($code, $reasonPhrase);
	}

}