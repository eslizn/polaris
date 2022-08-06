<?php

namespace Polaris\Http\Server\Drivers;

use Polaris\Config\ConfigInterface;
use Polaris\Container\Container;
use Polaris\Http\Exception;
use Polaris\Http\Cookies;
use Polaris\Http\Events\BootstrapEvent;
use Polaris\Http\Events\RequestedEvent;
use Polaris\Http\Events\RequestEvent;
use Polaris\Http\Events\StartedEvent;
use Polaris\Http\Events\StartEvent;
use Polaris\Http\Events\TaskFinishEvent;
use Polaris\Http\Events\TaskStartEvent;
use Polaris\Http\Events\WorkerExitEvent;
use Polaris\Http\Events\WorkerStartedEvent;
use Polaris\Http\Events\WorkerStoppedEvent;
use Polaris\Http\Factory\RequestFactory;
use Polaris\Http\Response;
use Polaris\Http\Server\ServerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Server;
use Swoole\Server\Task;
use Throwable;

/**
 *
 */
class Swoole implements ServerInterface
{

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var EventDispatcherInterface
     */
    protected EventDispatcherInterface $dispatcher;

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @param ContainerInterface $container
     * @throws \Polaris\Exception
     */
    public function __construct(ContainerInterface $container)
    {
        try {
            $this->container = $container;
            $this->dispatcher = $container->get(EventDispatcherInterface::class);
            $this->config = $container->get(ConfigInterface::class);
        } catch (\Polaris\Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \Polaris\Container\Exception
     */
    public function bootstrap()
    {
        $enableCoroutine = $this->config->get('swoole.runtime.enableCoroutine', SWOOLE_HOOK_ALL);
        if ($enableCoroutine) {
            \Swoole\Runtime::enableCoroutine($enableCoroutine);
        }
        $this->container->set(new \Swoole\Http\Server(
            $this->config->get('http.server.listen', getenv('HOST') ?: '0.0.0.0'),
            $this->config->get('http.server.port', getenv('PORT') ?: 3000)
        ));
        foreach (get_class_methods($this) as $method) {
            if (preg_match('/^on(\w+)$/i', $method, $matches)) {
                $this->container->get(\Swoole\Http\Server::class)->on($matches[1], [$this, $method]);
            }
        }
        $this->dispatcher->dispatch(new StartEvent($this->container, $this->container->get(\Swoole\Http\Server::class)));
        $this->container->get(\Swoole\Http\Server::class)->set($this->config->get('http.server.settings', []));
        $this->container->get(\Swoole\Http\Server::class)->start();
    }

    /**
     * @param Server $server
     */
    public function onStart(Server $server)
    {
        $this->dispatcher->dispatch(new StartedEvent($this->container, $server));
    }

    /**
     * @param Server $server
     * @param int $id
     */
    public function onWorkerStart(Server $server, int $id)
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        $this->dispatcher->dispatch(new WorkerStartedEvent($this->container, $server, $id));
        //@todo without task?
        $this->dispatcher->dispatch(new BootstrapEvent($this->container));
    }

    /**
     * @param Server $server
     * @param int $id
     */
    public function onWorkerStop(Server $server, int $id)
    {
        $this->dispatcher->dispatch(new WorkerStoppedEvent($this->container, $server, $id));
    }

    /**
     * @param Server $server
     * @param int $id
     */
    public function onWorkerExit(Server $server, int $id)
    {
        $this->dispatcher->dispatch(new WorkerExitEvent($this->container, $server, $id));
    }

    /**
     * @param Server $server
     * @param Task|int $task
     * @param int $worker_id
     * @param mixed $data
     */
    public function onTask(Server $server, $task, int $worker_id = 0, $data = null)
    {
        if (is_object($task)) {
            $worker_id = $task->worker_id;
            $data = $task->data;
            $id = $task->id;
        } else {
            $id = $task;
        }
        $this->dispatcher->dispatch(new TaskStartEvent($this->container, $server, $id, $worker_id, $data, is_object($task) ? $task : null));
    }

    /**
     * @param Server $server
     * @param int $task_id
     * @param mixed $data
     */
    public function onFinish(Server $server, int $task_id, $data)
    {
        $this->dispatcher->dispatch(new TaskFinishEvent($this->container, $server, $task_id, $data));
    }

    /**
     * @param \Swoole\Http\Request $reader
     * @param \Swoole\Http\Response $writer
     * @throws \Polaris\Exception
     */
    public function onRequest(\Swoole\Http\Request $reader, \Swoole\Http\Response $writer)
    {
        try {
            $container = new Container();
            $container->addResolver($this->container)->set($reader)->set($writer);
            $container->set(RequestFactory::createFromSwoole($reader));
            $requestEvent = new RequestEvent($container);
            $this->dispatcher->dispatch($requestEvent);
            $requestedEvent = new RequestedEvent($container);
            $this->terminate($container, $requestEvent->getRequest(), $requestedEvent->getResponse());
            $this->dispatcher->dispatch($requestedEvent);
        } catch (\Polaris\Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param ContainerInterface $container
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    protected function terminate(ContainerInterface $container, ServerRequestInterface $request, ResponseInterface $response)
    {
        try {
            $writer = $container->get(\Swoole\Http\Response::class);
            $writer->status($response->getStatusCode());
            foreach ($response->getHeaders() as $name => $headers) {
                if (strcasecmp($name, 'Set-Cookie')) {
                    $writer->header($name, implode(', ', $headers));
                } else {
                    $cookie = Cookies::parse(implode('; ', $headers));
                    foreach ($cookie->cookies ?: [] as $key => $value) {
                        $writer->cookie($key, $value, $cookie->expires ?: null, $cookie->path ?: '/', $cookie->domain ?: '', $cookie->secure ?: false, $cookie->httponly ?: false);
                    }
                }
            }
            if (!$response->getHeader('Server')) {
                $writer->header('Server', 'Petrel');
            }
            if ($response->getBody()->getSize()) {
                if ($response instanceof Response\FileResponse) {
                    $file = $response->getBody()->getContents();
                    $writer->sendfile($file);//sendfile will end
                } else {
                    $writer->write($response->getBody()->getContents());
                    $writer->end();
                }
            } else {
                $writer->end();
            }
        } catch (Throwable $e) {
            if (isset($writer)) {
                $writer->end();
            }
            throw $e;
        }
    }

}
