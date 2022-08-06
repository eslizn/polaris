<?php

namespace Polaris\Http\Events;

use Psr\Container\ContainerInterface;
use Swoole\Server;

/**
 *
 */
class TaskFinishEvent  extends StartEvent
{

    /**
     * @var int
     */
    protected int $id;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @param ContainerInterface $container
     * @param Server $server
     * @param int $id
     * @param mixed $data
     */
    public function __construct(ContainerInterface $container, Server $server, int $id, $data)
    {
        parent::__construct($container, $server);
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

}