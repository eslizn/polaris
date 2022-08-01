<?php

namespace Polaris\Pool;

/**
 *
 */
class Manager
{

    /**
     * @var array
     */
    protected static array $pools = [];

    /**
     * @param string $name
     * @param callable $factory
     * @param int $size
     * @param float $timeout
     * @param callable|null $destruct
     * @return Pool
     * @throws Exception
     */
    public static function create(string $name, callable $factory, int $size = 32, float $timeout = 0.1, ?callable $destruct = null): Pool
    {
        if (!static::available()) {
            throw new Exception('current not support pool', -__LINE__);
        }
        if (isset(static::$pools[$name])) {
            throw new Exception(sprintf('pool: %s already exists', $name), -__LINE__);
        }
        static::$pools[$name] = new Pool($factory, $size, $timeout, $destruct);
        return static::$pools[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return static::available() && isset(static::$pools[$name]);
    }

    /**
     * @param string $name
     * @return Pool
     * @throws Exception
     */
    public static function get(string $name): Pool
    {
        if (!static::has($name)) {
            throw new Exception(sprintf('pool: %s not exists', $name), -__LINE__);
        }
        return static::$pools[$name];
    }

    /**
     * @return bool
     */
    public static function available(): bool
    {
        return class_exists(\Swoole\Coroutine::class) &&
            \Swoole\Coroutine::getCid() > -1;
    }

}