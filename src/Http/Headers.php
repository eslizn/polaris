<?php

namespace Polaris\Http;

/**
 *
 */
class Headers implements \ArrayAccess, \Countable, \JsonSerializable
{

	/**
	 * @var array
	 */
	protected array $attributes = [];

	/**
	 * @param array $attributes
	 */
	public function __construct(array $attributes = [])
	{
		foreach ($attributes as $k => $v) {
			$this->offsetSet($k, $v);
		}
	}

	/**
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->attributes[$offset]);
	}

	/**
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->attributes[$offset] ?? null;
	}

	/**
	 * @param string $offset
	 * @param string $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if (!isset($this->attributes[$offset])) {
			$this->attributes[$offset] = [];
		}
		$this->attributes[$offset][] = $value;
	}

	/**
	 * @param string $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->attributes[$offset]);
	}

	/**
	 * @return int
	 */
	public function count(): int
	{
		return sizeof($this->attributes);
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return $this->attributes;
	}

}