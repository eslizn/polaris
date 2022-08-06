<?php

namespace Polaris\Dispatch;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 *
 */
class Dispatcher implements ListenerProviderInterface, EventDispatcherInterface
{

    /**
     * @var array
     */
    protected array $listeners = [];

    /**
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        return $this->listeners[get_class($event)] ?? [];
    }

    /**
     * @param object $event
     * @return object
     */
    public function dispatch(object $event): object
    {
        if (($event instanceof StoppableEventInterface &&
            $event->isPropagationStopped())) {
            return $event;
        }
        foreach ($this->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
        return $event;
    }

    /**
     * @param mixed $event
     * @param mixed $callable
     * @return static
     */
    public function addListener($event, $callable): self
    {
        if (is_object($event)) {
            $event = get_class($event);
        }
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = $callable;
        return $this;
    }

    /**
     * @return array
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

}