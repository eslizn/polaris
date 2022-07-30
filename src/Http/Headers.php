<?php

namespace Polaris\Http;

/**
 *
 */
class Headers implements \ArrayAccess, \Countable, \JsonSerializable
{

    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    protected static array $special = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];

	/**
	 * @var array
	 */
	protected array $attributes = [];

    /**
     * Create new headers collection with data extracted from Swoole Request
     *
     * @param \Swoole\Http\Request $request
     * @return static
     * @throws Exception
     */
    public static function createFromSwoole(\Swoole\Http\Request $request): self
    {
        if (empty($request) || !class_exists('\Swoole\Http\Request')) {
            throw new Exception('invalid request object', -__LINE__);
        }
        $data = [];
        foreach ($request->header ?: [] as $key => $value) {
            $key = strtoupper($key);
            if ($key !== 'HTTP_CONTENT_LENGTH') {
                $data[static::reconstructOriginalKey($key)] =  $value;
            }
        }
        return new static($data);
    }

    /**
     * Create new headers collection with data extracted from
     * the PHP global environment
     *
     * @param array $globals Global server variables
     *
     * @return static
     */
    public static function createFromGlobals(array $globals): self
    {
        $data = [];
        $globals = static::determineAuthorization($globals);
        foreach ($globals as $key => $value) {
            $key = strtoupper($key);
            if (isset(static::$special[$key]) || strpos($key, 'HTTP_') === 0) {
                if ($key !== 'HTTP_CONTENT_LENGTH') {
                    $data[static::reconstructOriginalKey($key)] =  $value;
                }
            }
        }

        return new static($data);
    }

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

    /**
     * If HTTP_AUTHORIZATION does not exist tries to get it from
     * getallheaders() when available.
     *
     * @param array $globals The Slim application Environment
     *
     * @return array
     */
    public static function determineAuthorization(array $globals): array
    {
        $authorization = $globals['HTTP_AUTHORIZATION'] ?? null;

        if (empty($authorization) && is_callable('getallheaders')) {
            $headers = getallheaders();
            $headers = array_change_key_case($headers, CASE_LOWER);
            if (isset($headers['authorization'])) {
                $globals['HTTP_AUTHORIZATION'] = $headers['authorization'];
            }
        }

        return $globals;
    }

    /**
     * Reconstruct original header name
     *
     * This method takes an HTTP header name from the Environment
     * and returns it as it was probably formatted by the actual client.
     *
     * @param string $key An HTTP header key from the $_SERVER global variable
     *
     * @return string The reconstructed key
     *
     * @example CONTENT_TYPE => Content-Type
     * @example HTTP_USER_AGENT => User-Agent
     */
    private static function reconstructOriginalKey(string $key): string
    {
        if (strpos($key, 'HTTP_') === 0) {
            $key = substr($key, 5);
        }
        return strtr(ucwords(strtr(strtolower(str_replace('-', '_', $key)), '_', ' ')), ' ', '-');
    }

}