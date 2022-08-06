<?php

namespace Polaris\Http\Events;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
class RequestedEvent extends RequestEvent
{

    /**
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->container->get(ResponseInterface::class);
    }

}