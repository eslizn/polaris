<?php

namespace Polaris\Http\Events;

use Polaris\Events\AbstractEvent;
use Psr\Container\ContainerInterface;
use Swoole\Server;

/**
 *
 */
class StartEvent extends AbstractEvent
{

    /**
     * @var Server
     */
    protected Server $server;

    /**
     * @param ContainerInterface $container
     * @param Server $server
     */
    public function __construct(ContainerInterface $container, Server $server)
    {
        parent::__construct($container);
        $this->server = $server;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

}