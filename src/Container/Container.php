<?php

namespace Polaris\Container;

use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Throwable;

/**
 *
 */
class Container implements ContainerInterface
{

    /**
     * @var array
     */
    protected array $instances = [];

    /**
     * @var array
     */
    protected array $singletons = [];

    /**
     * @var callable[]
     */
    protected array $factories = [];

    /**
     * @var ContainerInterface[]
     */
    protected array $resolvers = [];

    /**
     * @var array
     */
    protected array $abstracts = [];

    /**
     * @param string $id
     * @param callable $factory
     * @return static
     */
    public function factory(string $id, $factory): self
    {
        $this->factories[$id] = $factory;
        return $this;
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return static
     */
    public function singleton(string $id, $factory): self
    {
        $this->singletons[$id] = $factory;
        return $this;
    }

    /**
     * @param ContainerInterface $resolver
     * @return static
     */
    public function addResolver(\Psr\Container\ContainerInterface $resolver): self
    {
        $this->resolvers[] = $resolver;
        return $this;
    }

    /**
     * @param mixed $id
     * @param mixed $value
     * @return static
     * @throws Throwable
     */
    public function set($id, $value = null): self
    {
        if (func_num_args() < 2) {
            if (is_object($id)) {
                $value = $id;
                $id = get_class($value);
            } else {
                $value = $this->make($id);
            }
            foreach (is_object($value) ? class_implements($value) : [] as $abstract) {
                $this->abstracts[$abstract] = $value;
            }
        }
        $this->instances[$id] = $value;
        return $this;
    }

    /**
     * @param string $id
     * @return mixed
     * @throws Throwable
     */
    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (isset($this->singletons[$id])) {
            return $this->instances[$id] = $this->invoke($this->singletons[$id]);
        }
        if (isset($this->factories[$id])) {
            return $this->invoke($this->factories[$id]);
        }
        if (isset($this->abstracts[$id])) {
            return $this->abstracts[$id];
        }
        foreach ($this->resolvers as $resolver) {
            if ($resolver->has($id)) {
                return $resolver->get($id);
            }
        }
        throw new Exception(sprintf('"%s" not found', $id), -__LINE__);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        if (isset($this->instances[$id]) ||
            isset($this->singletons[$id]) ||
            isset($this->factories[$id]) ||
            isset($this->abstracts[$id])) {
            return true;
        }
        foreach ($this->resolvers as $resolver) {
            if ($resolver->has($id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param ReflectionFunctionAbstract $closure
     * @return array
     * @throws Exception
     */
    protected function resolveFunctionParameters(ReflectionFunctionAbstract $closure): array
    {
        try {
            $parameters = [];
            foreach ($closure->getParameters() as $p) {
                $name = $p->getType() && !$p->getType()->isBuiltin() ? $p->getType()->getName() : $p->getName();
                if ($this->has($name)) {
                    $parameters[] = $this->get($name);
                } else {
                    $parameters[] = $p->isDefaultValueAvailable() ? $p->getDefaultValue() : null;
                }
            }
            return $parameters;
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     *
     * @param callable $factory
     * @return mixed
     * @throws Exception
     */
    public function invoke($factory)
    {
        try {
            if (is_array($factory)) {
                list($class, $method) = $factory;
                if (is_string($class)) {
                    $class = $this->make($class);
                }
                if (empty($method)) {
                    $method = '__invoke';
                }
                if (!method_exists($class, $method)) {
                    throw new Exception(sprintf('%s::%s() does not exist', get_class($class), $method), -__LINE__);
                }
                $reflect = new ReflectionMethod($class, $method);
                return $reflect->invokeArgs($class, $this->resolveFunctionParameters($reflect));
            } else {
                $reflect = new ReflectionFunction($factory);
                return $reflect->invokeArgs($this->resolveFunctionParameters($reflect));
            }
        } catch (Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $class
     * @return object|null
     * @throws Exception
     */
    public function make(string $class): ?object
    {
        try {
            $reflect = new ReflectionClass($class);
            return $reflect->newInstanceArgs($reflect->getConstructor() ?
                $this->resolveFunctionParameters($reflect->getConstructor()) :
                []
            );
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

}