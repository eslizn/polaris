<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim-Http
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim-Http/blob/master/LICENSE (MIT License)
 */
namespace Polaris\Http;

/**
 * Provides a PSR-7 implementation of a reusable raw request body
 */
class RequestBody extends Body
{

	/**
	 * @var resource
	 */
	private $resource = null;

	/**
	 * RequestBody constructor.
	 * @param null $data
	 */
    public function __construct($data = null)
    {
		if (!is_null($data)) {
			//@todo
			$this->resource = fopen('php://memory','r+');
			fwrite($this->resource, $data);
			rewind($this->resource);
			parent::__construct($this->resource);
		} else {
			$this->resource = fopen('php://temp', 'w+');
			stream_copy_to_stream(fopen('php://input', 'r'), $this->resource);
			rewind($this->resource);
			parent::__construct($this->resource);
		}
    }

	/**
	 *
	 */
    public function __destruct()
	{
		if ($this->resource) {
			fclose($this->resource);
		}
	}

}
