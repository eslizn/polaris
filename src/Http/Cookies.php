<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim-Http
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim-Http/blob/master/LICENSE (MIT License)
 */
namespace Polaris\Http;

use InvalidArgumentException;

/**
 * Class Cookies
 *
 * @package Polaris\Http
 */
class Cookies implements CookiesInterface
{
    /**
     * Cookies from HTTP request
     *
     * @var array
     */
    protected $requestCookies = [];

    /**
     * Cookies for HTTP response
     *
     * @var array
     */
    protected $responseCookies = [];

    /**
     * Default cookie properties
     *
     * @var array
     */
    protected $defaults = [
        'value' => '',
        'domain' => null,
        'hostonly' => null,
        'path' => null,
        'expires' => null,
        'secure' => false,
        'httponly' => false
    ];

    /**
     * Create new cookies helper
     *
     * @param array $cookies
     */
    public function __construct(array $cookies = [])
    {
        $this->requestCookies = $cookies;
    }

    /**
     * Set default cookie properties
     *
     * @param array $settings
     */
    public function setDefaults(array $settings)
    {
        $this->defaults = array_replace($this->defaults, $settings);
    }

    /**
     * Get request cookie
     *
     * @param  string $name    Cookie name
     * @param  mixed  $default Cookie default value
     *
     * @return mixed Cookie value if present, else default
     */
    public function get($name, $default = null)
    {
        return isset($this->requestCookies[$name]) ? $this->requestCookies[$name] : $default;
    }

    /**
     * Set response cookie
     *
     * @param string       $name  Cookie name
     * @param string|array $value Cookie value, or cookie properties
     */
    public function set($name, $value)
    {
        if (!is_array($value)) {
            $value = ['value' => (string)$value];
        }
        $this->responseCookies[$name] = array_replace($this->defaults, $value);
    }

	/**
	 * @param string $name
	 * @param array $properties
	 * @return string
	 */
	public static function build($name, $properties = [])
	{
		if (is_string($properties)) {
			$properties = ['value' => $properties];
		}
		if (!is_array($properties)) {
			throw new InvalidArgumentException('properties value must be a array.');
		}
		$cookies = [];
		$cookies[] = urlencode($name) . '=' . (isset($properties['value']) ? $properties['value'] : '');
		unset($properties['value']);
		foreach ($properties as $k => $v) {
			if (in_array($k, ['secure', 'hostonly', 'httponly'])) {
				if ($v) {
					$cookies[] = $k;
				}
			} else if (in_array($k, ['expires', 'path', 'domain'])) {
				$cookies[] = $k . '=' . $v;
			} else {
				$cookies[] = urlencode($k) . '=' . urlencode($v);
			}
		}
		return implode('; ', $cookies);
	}

	/**
	 * parse cookies
	 *
	 * like: http_parse_cookie
	 *
	 * @param string $cookies
	 * @param int $flags
	 * @param array $extras
	 * @return \stdClass
	 */
	public static function parse($cookies, $flags = 0, $extras = [])
    {
        if (is_array($cookies) === true) {
			$cookies = isset($cookies[0]) ? $cookies[0] : '';
        }

        if (is_string($cookies) === false) {
            throw new InvalidArgumentException('Cannot parse Cookie data. Header value must be a string.');
        }

		$parser = new \stdClass();
		$parser->cookies = [];
		$parser->extras = [];
		$cookies = array_filter(array_map('trim', explode(';', $cookies)));
		if (empty($cookies) || !strpos($cookies[0], '=')) {
			return $parser;
		}
		$options = ['expires', 'path', 'domain', 'secure', 'httponly'];
		foreach ($cookies as $cookie) {
			$cookie = explode('=', $cookie, 2);
			$key = trim($flags & HTTP_COOKIE_PARSE_RAW ? $cookie[0] : urldecode($cookie[0]));
			if (sizeof($cookie) > 1) {
				$value = trim($flags & HTTP_COOKIE_PARSE_RAW ? $cookie[1] : urldecode($cookie[1]), " \n\r\t\0\x0B\"");
			} else {
				$value = true;
			}
			if (in_array($key, $options)) {
				$parser->$key = $value;
			} else if (in_array($key, $allowed_extras)) {
				$parser->extras[$key] = $value;
			} else {
				$parser->cookies[$key] = $value;
			}
		}
		if ($flags & HTTP_COOKIE_SECURE) {
			$parser->secure = true;
		}
		if ($flags & HTTP_COOKIE_HTTPONLY) {
			$parser->httponly = true;
		}
		return $parser;
    }
}
