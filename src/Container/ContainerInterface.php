<?php

namespace Polaris\Container;

/**
 *
 */
interface ContainerInterface extends \Psr\Container\ContainerInterface, FactoryInterface
{

    /**
     * @param mixed $factory
     * @return mixed
     */
    public function invoke($factory);

    /**
     * @param string $class
     * @return mixed
     */
    public function make(string $class);

    /**
     * @param mixed $id
     * @param mixed $value
     * @return static
     */
    public function set($id, $value = null): self;

}