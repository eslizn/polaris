<?php
namespace Polaris\Http\Response;

use Polaris\Http\Headers;
use Polaris\Http\Response;

/**
 * Class JsonResponse
 * @package Polaris\Http\Response
 */
class JsonResponse extends Response
{

	/**
	 * PlainResponse constructor.
	 * @param mixed $data
	 * @param int $status
	 * @param null $headers
	 */
	public function __construct($data = [], $status = 200, $headers = null)
	{
		parent::__construct($status, $headers ?: new Headers(['Content-Type' => 'application/json']), json_encode($data));
		if (json_last_error()) {
			throw new \RuntimeException(json_last_error_msg(), -(0xfe00 | abs(json_last_error())));
		}
	}

}