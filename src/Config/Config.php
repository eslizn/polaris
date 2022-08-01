<?php

namespace Polaris\Config;

/**
 *
 */
class Config implements ConfigInterface
{

	/**
	 * @var string
	 */
	protected string $path;

	/**
	 * @var array
	 */
	protected array $data = [];

    /**
     * @param string $path
     * @throws Exception
     */
	public function __construct(string $path)
	{
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		$this->path = realpath($path);
		if ($this->path === false) {
			throw new Exception('invalid path: ' . $path, -__LINE__);
		}
	}

	/**
	 * @param string $name
	 * @param null $default
	 * @return mixed
	 */
	public function get(string $name, $default = null)
	{
        $this->lazyLoad($name);
        $segments = explode('.', $name);
        $ptr = &$this->data;
        while (sizeof($segments)) {
            $segment = array_shift($segments);
            if (!isset($ptr[$segment])) {
                return $default;
            }
            $ptr = &$ptr[$segment];
        }
		return $ptr;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return static
	 */
	public function set(string $name, $value): self
	{
        $this->lazyLoad($name);
        $segments = explode('.', $name);
        $ptr = &$this->data;
        while (sizeof($segments)) {
            $segment = array_shift($segments);
            if (!isset($ptr[$segment])) {
                $ptr[$segment] = [];
            }
            $ptr = &$ptr[$segment];
        }
        $ptr = $value;
		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has(string $name): bool
	{
        $this->lazyLoad($name);
        $segments = explode('.', $name);
        $ptr = &$this->data;
        while (sizeof($segments)) {
            $segment = array_shift($segments);
            if (!isset($ptr[$segment])) {
                return false;
            }
            $ptr = &$ptr[$segment];
        }
		return true;
	}

	/**
	 * @param string $name
	 * @return static
	 */
	public function del(string $name): self
	{
        $this->lazyLoad($name);
        $segments = explode('.', $name);
        $ptr = &$this->data;
        while (sizeof($segments)) {
            $segment = array_shift($segments);
            if (!isset($ptr[$segment])) {
                return $this;
            }
            if (sizeof($segments)) {
                $ptr = &$ptr[$segment];
            } else {
                unset($ptr[$segment]);
            }
        }
		return $this;
	}

	/**
	 * @param string $name
	 * @return static
	 */
	protected function lazyLoad(string $name): self
	{
		$index = strpos($name, '.');
		$prefix = $index ? substr($name, 0, $index) : $name;
		$file = $this->path . DIRECTORY_SEPARATOR . $prefix . '.php';
		if (!isset($this->data[$prefix]) && file_exists($file)) {
			$this->data[$prefix] = require($file);
		}
		return $this;
	}

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->del($offset);
    }

}
