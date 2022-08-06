<?php

namespace Polaris\Http\Exception;

use Polaris\Http\Exception;
use Polaris\Http\Response;

/**
 *
 */
class HttpException extends Exception
{

    /**
     * @var integer
     */
    protected int $statusCode;

    /**
     * @var string
     */
    protected string $statusText;

    /**
     * HttpException constructor.
     * @param int $statusCode
     * @param string $statusText
     */
    public function __construct(int $statusCode = 200, string $statusText = '')
    {
        if (empty($statusText) && isset(Response::$statusTexts[$statusCode])) {
            $statusText = Response::$statusTexts[$statusCode];
        }
        parent::__construct($statusText, $statusCode);
        list($this->statusCode, $this->statusText) = [$statusCode, $statusText];
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

}