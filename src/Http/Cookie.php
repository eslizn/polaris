<?php

namespace Polaris\Http;

defined('HTTP_COOKIE_PARSE_RAW') or define('HTTP_COOKIE_PARSE_RAW', 1);
defined('HTTP_COOKIE_SECURE ') or define('HTTP_COOKIE_SECURE ', 16);
defined('HTTP_COOKIE_HTTPONLY') or define('HTTP_COOKIE_HTTPONLY', 32);

/**
 *
 */
class Cookie
{

	/**
	 * @var string
	 */
	protected string $name;

	/**
	 * @var string|null
	 */
	protected ?string $value = null;

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
	protected bool $httpOnly = false;

	/**
	 * @param string $name
	 * @param string|null $value
	 * @param int $expires
	 * @param string|null $path
	 * @param string|null $domain
	 * @param bool $secure
	 * @param bool $httpOnly
	 */
	public function __construct(string $name,
								?string $value = null,
								int $expires = 0,
								?string $path = null,
								?string $domain = null,
								bool $secure = false,
								bool $httpOnly = false)
	{
		$this->name = $name;
		$this->value = $value;
		$this->expires = $expires;
		$this->path = $path;
		$this->domain = $domain;
		$this->secure = $secure;
		$this->httpOnly = $httpOnly;
	}

	/**
	 * @return string
	 */
	public function __toString(): string
	{
		$cookie = [
			$this->name => $this->value,
			'expires' => $this->expires,
			'path' => $this->path,
			'domain' => $this->domain,
		];
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

}