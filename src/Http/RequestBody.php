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
	 * RequestBody constructor.
	 * @param null $data
	 */
    public function __construct($data = null)
    {
		if (!is_null($data)) {
			$resource = fopen('php://memory','r+');
			fwrite($resource, $data);
			rewind($resource);
			parent::__construct($resource);
		} else {
			$resource = fopen('php://memory', 'w+');
			parent::__construct($resource);
		}
    }

}
