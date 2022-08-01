<?php

namespace Polaris\Http\Factory;

use Polaris\Http\Body;
use Polaris\Http\Exception;
use Polaris\Http\Stream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 *
 */
class StreamFactory implements StreamFactoryInterface
{

	/**
	 * @param string $content
	 * @return StreamInterface
	 * @throws Exception
	 */
	public function createStream(string $content = ''): StreamInterface
	{
		return new Body($content);
	}

	/**
	 * @param string $filename
	 * @param string $mode
	 * @return StreamInterface
	 * @throws Exception
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
	{
		return $this->createStreamFromResource(fopen($filename, $mode));
	}

	/**
	 * @param resource $resource
	 * @return StreamInterface
	 * @throws Exception
	 */
	public function createStreamFromResource($resource): StreamInterface
	{
		return new Stream($resource);
	}

}