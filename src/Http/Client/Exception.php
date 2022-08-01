<?php

namespace Polaris\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 *
 */
class Exception extends \Polaris\Http\Exception
{

    /**
     * @var RequestInterface|null
     */
    protected ?RequestInterface $request;

    /**
     * @param RequestInterface|null $request
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(?RequestInterface $request = null,
                                string $message = '',
                                int $code = 0,
                                Throwable $previous = null)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

}