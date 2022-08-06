<?php

namespace Polaris;

use Polaris\Config\Config;
use Polaris\Config\ConfigInterface;
use Polaris\Container\Container;
use Polaris\Container\ContainerInterface;
use Polaris\Dispatch\Dispatcher;
use Polaris\Events\BootstrapEvent;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 *
 */
class Application extends Dispatcher
{

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @param string|null $path
     * @throws Exception
     */
    public function __construct(?string $path = null)
    {
        if (is_null($path)) {
            $path = dirname(__DIR__, 5);
        }
        $this->config = new Config($path . DIRECTORY_SEPARATOR . 'config');
        $this->config->set('app.path', $path);
        $this->container = new Container();
        $this->container->set($this->container);
        $this->container->set($this->config);
        $this->container->set($this);
    }

    /**
     * @param mixed $event
     * @param callable $callable
     * @return static
     * @throws Exception
     */
    public function addListener($event, $callable): self
    {
        return parent::addListener($event, is_string($callable) ? $this->container->make($callable) : $callable);
    }

    /**
     * @param array $listeners
     * @return static
     * @throws Exception
     */
    public function addListeners(array $listeners): self
    {
        foreach ($listeners as $event => $list) {
            foreach ($list as $listener) {
                $this->addListener($event, $listener);
            }
        }
        return $this;
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
     *
     */
    public function bootstrap()
    {
        $this->dispatch(new BootstrapEvent($this->container));
    }

}