<?php

namespace Polaris\Http\Events;

use Psr\Container\ContainerInterface;
use Swoole\Server;

/**
 *
 */
class TaskStartEvent extends StartEvent
{

    /**
     * @var int
     */
    protected int $id;

    /**
     * @var int
     */
    protected int $worker_id;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var Server\Task|null
     */
    protected ?Server\Task $task;

    /**
     * @param ContainerInterface $container
     * @param Server $server
     * @param int $id
     * @param int $worker_id
     * @param mixed $data
     * @param Server\Task|null $task
     */
    public function __construct(ContainerInterface $container, Server $server, int $id, int $worker_id, $data, Server\Task $task = null)
    {
        parent::__construct($container, $server);
        $this->id = $id;
        $this->worker_id = $worker_id;
        $this->data = $data;
        $this->task = $task;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->worker_id;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function finish()
    {
        return $this->task ? $this->task->finish($this->data) : $this->server->finish($this->data);
    }

}