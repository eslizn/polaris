<?php

namespace Polaris\Http\Response;

use Polaris\Http\Body;
use Polaris\Http\Exception;
use Polaris\Http\Headers;
use Polaris\Http\Response;

/**
 *
 */
class JsonResponse extends Response
{

    /**
     * JsonResponse constructor.
     *
     * @param mixed $data
     * @param int $status
     * @param null $headers
     * @throws Exception
     */
    public function __construct($data = [], $status = 200, $headers = null)
    {
        parent::__construct($status, $headers ?: new Headers(['Content-Type' => 'application/json']), new Body(json_encode($data)));
        if (json_last_error()) {
            throw new Exception(json_last_error_msg(), -abs(json_last_error()));
        }
    }

}