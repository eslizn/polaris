<?php

namespace Polaris\Http\Server;

use Polaris\Events\BootstrapEvent;
use Polaris\Http\Server\Drivers\Standard;
use Polaris\Http\Server\Drivers\Swoole;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 *
 */
class Server
{

    /**
     * @param BootstrapEvent $event
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(BootstrapEvent $event)
    {
        $event->getContainer()->factory(ServerInterface::class, function (ContainerInterface $container) {
            return $container->make(class_exists('\Swoole\Http\Server') &&
            !strcasecmp(php_sapi_name(), 'cli') ? Swoole::class : Standard::class);
        });
        $event->getContainer()->get(ServerInterface::class)->bootstrap();
    }

}