<?php

namespace Polaris\Database\Exception;

use Polaris\Database\Exception;
use Throwable;

/**
 *
 */
class NoRecordException extends Exception
{

    /**
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = 'record not found', int $code = -__LINE__, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
