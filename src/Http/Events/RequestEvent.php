<?php

namespace Polaris\Http\Events;

use Polaris\Events\AbstractEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class RequestEvent extends AbstractEvent
{

    /**
     * @return ServerRequestInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->container->get(ServerRequestInterface::class);
    }

}