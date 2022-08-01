<?php

namespace Polaris\Config;

/**
 *
 */
interface ConfigInterface extends \ArrayAccess
{

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null);

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function set(string $name, $value): self;

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * @param string $name
     * @return static
     */
    public function del(string $name): self;

}