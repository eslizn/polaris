<?php

namespace Polaris\Http\Router;

use Psr\Container\ContainerInterface;

/**
 *
 */
class ArgumentResolver implements ContainerInterface
{

    /**
     * @var array
     */
    protected array $arguments = [];

    /**
     * @param array $arguments
     */
    public function __construct(array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get(string $id)
    {
        return $this->arguments[$id] ?? null;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->arguments[$id]);
    }

}