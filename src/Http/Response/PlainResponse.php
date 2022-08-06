<?php

namespace Polaris\Http\Response;

use Polaris\Http\Body;
use Polaris\Http\Exception;
use Polaris\Http\Headers;
use Polaris\Http\Response;

/**
 *
 */
class PlainResponse extends Response
{

    /**
     * @param string $data
     * @param int $status
     * @param ?Headers $headers
     * @throws Exception
     */
    public function __construct(string $data = '', int $status = 200, ?Headers $headers = null)
    {
        parent::__construct($status, $headers ?: new Headers(['Content-Type' => 'text/plain']), new Body($data));
    }

}