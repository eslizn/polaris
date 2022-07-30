<?php

namespace Polaris\Container;

use Psr\Container\ContainerInterface;

/**
 *
 */
interface FactoryInterface
{

    /**
     * @param string $id
     * @param mixed $factory
     * @return static
     */
    public function factory(string $id, $factory): self;

    /**
     * @param string $id
     * @param mixed $factory
     * @return static
     */
    public function singleton(string $id, $factory): self;

    /**
     * @param ContainerInterface $resolver
     * @return static
     */
    public function addResolver(ContainerInterface $resolver): self;

}