<?php

namespace Polaris\Http\Middleware;

use JsonSerializable;
use Polaris\Config\ConfigInterface;
use Polaris\Container\ContainerInterface;
use Polaris\Http\Exception;
use Polaris\Http\Response;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 *
 */
class ActionMiddleware implements MiddlewareInterface
{

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var callable
     */
    protected $closure;

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @param ContainerInterface $container
     * @param mixed $closure
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container, $closure)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
        $this->closure = $closure;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_array($this->closure) && is_string($this->closure[0])) {
            $class = $this->closure[0];
            if (!class_exists($this->closure[0])) {
                $class = $this->config->get('http.controllers.namespace', 'App\\Http\\Controllers\\') . $class;
                if (!class_exists($class)) {
                    $class = $this->closure[0];
                }
            }
            $this->closure[0] = $this->container->make($class);
        }
        $response = $this->container->invoke($this->closure);
        if (is_null($response)) {
            return new Response();
        } else if (is_scalar($response)) {
            return new Response\PlainResponse($response);
        } else if (is_array($response) || ($response instanceof JsonSerializable)) {
            return new Response\JsonResponse($response);
        } else {
            return $response;
        }
    }

}