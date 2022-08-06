<?php

namespace Polaris\Http\Server\Drivers;

use Polaris\Events\BootstrapEvent;
use Polaris\Http\Cookies;
use Polaris\Http\Events\RequestedEvent;
use Polaris\Http\Events\RequestEvent;
use Polaris\Http\Exception;
use Polaris\Http\Factory\RequestFactory;
use Polaris\Http\Server\ServerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 */
class Standard implements ServerInterface
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
     * @param ContainerInterface $container
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->dispatcher = $container->get(EventDispatcherInterface::class);
    }

    /**
     *
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws \Polaris\Container\Exception
     */
    public function bootstrap()
    {
        $this->dispatcher->dispatch(new BootstrapEvent($this->container));
        $this->container->set(RequestFactory::createFromGlobals($_SERVER));
        $requestEvent = new RequestEvent($this->container);
        $this->dispatcher->dispatch($requestEvent);
        $requestedEvent = new RequestedEvent($this->container);
        $this->terminate($requestEvent->getRequest(), $requestedEvent->getResponse());
        $this->dispatcher->dispatch($requestedEvent);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    protected function terminate(ServerRequestInterface $request, ResponseInterface $response)
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $headers) {
            if (strcasecmp($name, 'Set-Cookie')) {
                header(sprintf('%s: %s', $name, implode(', ', $headers)), true);
            } else {
                $cookie = Cookies::parse(implode('; ', $headers));
                foreach ($cookie->cookies ?: [] as $key => $value) {
                    setcookie($key, $value, $cookie->expires ?: null, $cookie->path ?: '/', $cookie->domain ?: '', $cookie->secure ?: false, $cookie->httponly ?: false);
                }
            }
        }
//        if ($response->getBody()->getSize()) {
//            if ($response instanceof Response\FileResponse) {
//                readfile($response->getBody()->getContents());
//            } else {
//                echo $response->getBody()->getContents();
//            }
//        }
    }

}
