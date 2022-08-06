<?php

namespace Polaris\Http\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Polaris\Config\ConfigInterface;
use Polaris\Http\Events\RequestEvent;
use Polaris\Http\Exception;
use Polaris\Http\Exception\HttpException;
use Polaris\Http\Middleware\ActionMiddleware;
use Polaris\Http\Pipeline;
use Polaris\Http\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function FastRoute\simpleDispatcher;

/**
 *
 */
class Router implements RouterInterface
{

    use RouteTrait;

    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $routes = $config->get('app.path') . '/config/routes.php';
        if (file_exists($routes)) {
            (function ($router) use ($routes) {
                require($routes);
            })($this);
        }
        $this->config = $config;
        $this->dispatcher = simpleDispatcher(function(RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute(...$route);
            }
        });
    }

    /**
     * @param RequestEvent $event
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __invoke(RequestEvent $event)
    {
        $route = $this->dispatcher->dispatch($event->getRequest()->getMethod(), $event->getRequest()->getUri()->getPath());
        $event->getContainer()->set($event->getRequest())
            ->addResolver(new ArgumentResolver($route[2] ?? []));

        $middleware = [];
        switch ($route[0]) {
            case Dispatcher::NOT_FOUND:
                $closure = function () {
                    throw new HttpException(404);
                };
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                $closure = function () {
                    throw new HttpException(405);
                };
                break;
            default:
                list($closure, $middleware) = $route[1];
                if (is_string($closure) && !is_callable($closure)) {
                    $closure = explode('@', $closure);
                    if (!isset($closure[1])) {
                        $closure[] = '__invoke';
                    }
                }
        }
        $middleware = array_merge($this->config->get('middleware', []), $middleware);
        $middleware[] = new ActionMiddleware($event->getContainer(), $closure);
        $pipeline = new Pipeline($event->getContainer(), new Response(), ...$middleware);
        $event->getContainer()->set($pipeline->handle($event->getRequest()));
    }

}