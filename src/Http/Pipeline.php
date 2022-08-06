<?php

namespace Polaris\Http;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 *
 */
class Pipeline implements RequestHandlerInterface
{

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * middleware stack
     *
     * @var array
     */
    protected array $stack = [];

    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $default;

    /**
     * Pipeline constructor.
     * @param ContainerInterface $container
     * @param ResponseInterface $default
     * @param mixed ...$middleware
     */
    public function __construct(ContainerInterface $container, ResponseInterface $default, ...$middleware)
    {
        $this->container = $container;
        $this->default = $default;
        $this->stack = $middleware;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $ctxRequest = $this->container->get(ServerRequestInterface::class);
        $this->container->set($request);
        $response = sizeof($this->stack) ?
            static::make($this->stack[0])->process($request, $this->next()) :
            $this->default;
        $this->container->set($ctxRequest);
        return $response;
    }

    /**
     * @return RequestHandlerInterface
     */
    protected function next(): RequestHandlerInterface
    {
        return new static($this->container, $this->default, ...(array_slice($this->stack, 1) ?: []));
    }

    /**
     * @param mixed $middleware
     * @return MiddlewareInterface
     * @throws Exception
     */
    protected function make($middleware): MiddlewareInterface
    {
        if (is_string($middleware)) {
            $middleware = $this->container->make($middleware);
        }
        if (!is_object($middleware)) {
            throw new Exception('middleware must object', -__LINE__);
        }
        if (!($middleware instanceof MiddlewareInterface)) {
            throw new Exception(sprintf('middleware: %s must instanceof MiddlewareInterface', get_class($middleware)), -__LINE__);
        }
        return $middleware;
    }

}