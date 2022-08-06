<?php

namespace Polaris\Http\Events;

use Psr\Container\ContainerInterface;
use Swoole\Server;

/**
 *
 */
class WorkerStartedEvent extends StartedEvent
{

    /**
     * @var integer
     */
    protected int $id;

    /**
     * @param ContainerInterface $application
     * @param Server $server
     * @param integer $id
     */
    public function __construct(ContainerInterface $application, Server $server, int $id)
    {
        parent::__construct($application, $server);
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

}