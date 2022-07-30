<?php

namespace Polaris\Http;

defined('HTTP_COOKIE_PARSE_RAW') or define('HTTP_COOKIE_PARSE_RAW', 1);
defined('HTTP_COOKIE_SECURE') or define('HTTP_COOKIE_SECURE', 16);
defined('HTTP_COOKIE_HTTPONLY') or define('HTTP_COOKIE_HTTPONLY', 32);

/**
 *
 * @property array $cookies
 * @property array $extras
 * @property int|null $expires
 * @property string|null $path
 * @property string|null $domain
 * @property bool $secure
 * @property bool $httponly
 *
 */
class Cookies implements \ArrayAccess
{

    /**
     * @var array
     */
	protected array $cookies = [];

    /**
     * @var array
     */
    protected array $extras = [];

	/**
	 * @var int|null
	 */
	protected ?int $expires = null;

	/**
	 * @var string|null
	 */
	protected ?string $path = null;

	/**
	 * @var string|null
	 */
	protected ?string $domain = null;

	/**
	 * @var bool
	 */
	protected bool $secure = false;

	/**
	 * @var bool
	 */
	protected bool $httponly = false;

    /**
     * @param array $cookies
     * @param int $expires
     * @param string|null $path
     * @param string|null $domain
     * @param bool $secure
     * @param bool $httpOnly
     */
	public function __construct(array $cookies = [],
								int $expires = 0,
								?string $path = null,
								?string $domain = null,
								bool $secure = false,
								bool $httpOnly = false)
	{
		$this->cookies = $cookies;
		$this->expires = $expires;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->httponly = $httpOnly;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		$cookie = array_merge($this->cookies, array_filter([
            'expires' => $this->expires,
            'path' => $this->path,
            'domain' => $this->domain,
        ]));
		$cookie = http_build_query($cookie, '', '; ');
		if ($this->secure) {
			$cookie .= '; secure';
		}
		if ($this->httpOnly) {
			$cookie .= '; httponly';
		}
		if ($this->domain) {
			$cookie .= '; hostonly';
		}
		return $cookie;
	}


    /**
     * @param string $cookies
     * @param int $flags
     * @param array $extras
     * @return static
     */
    public static function parse(string $cookies, int $flags = 0, array $extras = []): self
    {
        $cookies = array_filter(array_map('trim', explode(';', $cookies)));
        $object = new static();
        foreach ($cookies as $cookie) {
            $cookie = explode('=', $cookie, 2);
            $key = trim($flags & HTTP_COOKIE_PARSE_RAW ? $cookie[0] : urldecode($cookie[0]));
            if (sizeof($cookie) > 1) {
                $value = trim($flags & HTTP_COOKIE_PARSE_RAW ? $cookie[1] : urldecode($cookie[1]), " \n\r\t\0\x0B\"");
            } else {
                $value = true;
            }
            if (property_exists($object, $key) && !in_array($key, ['cookies', 'extras'])) {
                $object->$key = $value;
            } else if (in_array($key, $extras)) {
                $object->extras[$key] = $value;
            } else {
                $object->cookies[$key] = $value;
            }
        }
        return $object;
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->cookies[$offset]);
    }

    /**
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->cookies[$offset] ?? null;
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->cookies[$offset] = $value;
    }

    /**
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->cookies[$offset]);
    }

    /**
     * @param string $offset
     * @return mixed
     */
    public function __get(string $offset)
    {
        return $this->$offset;
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function __isset(string $offset)
    {
        return isset($this->$offset);
    }

    /**
     * @param string $offset
     * @return void
     */
    public function __unset($offset)
    {

    }

    /**
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function __set(string $offset, $value)
    {

    }

}