<?php

namespace Polaris\Http\Factory;

use Polaris\Http\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Swoole\Http\Request;

/**
 *
 */
class UriFactory implements UriFactoryInterface
{

	/**
	 * @param string $uri
	 * @return UriInterface
	 */
	public function createUri(string $uri = ''): UriInterface
	{
		return static::createFromString($uri);
	}

	/**
	 * Create new Uri from string.
	 *
	 * @param string $uri Complete Uri string
	 *     (i.e., https://user:pass@host:443/path?query).
	 *
	 * @return UriInterface
	 */
	public static function createFromString(string $uri): UriInterface
	{
		$parts = parse_url($uri);
		return new Uri(
			$parts['scheme'] ?? '',
			$parts['host'] ?? null,
			$parts['port'] ?? null,
			$parts['path'] ?? '',
			$parts['query'] ?? '',
			$parts['fragment'] ?? '',
			$parts['user'] ?? '',
			$parts['pass'] ?? ''
		);
	}

	/**
	 * Create new Uri from Swoole.
	 *
	 * @param Request $request
	 * @return UriInterface
	 */
	public static function createFromSwoole(Request $request): UriInterface
	{
		$host = $request->header['host'] ?? '127.0.0.1';
		$port = $request->server['server_port'] ?? 80;
		$last = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? false : strrpos($host, ':');
		return new Uri(
			'http',//@todo
			$last === false ? $host : substr($host, 0, $last),
			intval($port ?: substr($host, $last + 1)),
			$request->server['request_uri'] ?? '/',
			$request->server['query_string'] ?? '',
			'',
			'',//@todo
			''//@todo
		);
	}

	/**
	 * Create new Uri from environment.
	 *
	 * @param array $globals The global server variables.
	 *
	 * @return UriInterface
	 */
	public static function createFromGlobals(array $globals): UriInterface
	{
		$scheme = strcasecmp($globals['HTTPS'] ?? '', 'off') ? 'http' : 'https';
		$username = $globals['PHP_AUTH_USER'] ?? '';
		$password = $globals['PHP_AUTH_PW'] ?? '';
		$host = $globals['HTTP_HOST'] ?? $globals['SERVER_NAME'];
		$port = $globals['SERVER_PORT'] ?? 80;
		if (preg_match('/^(\[[a-fA-F0-9:.]+\])(:\d+)?\z/', $host, $matches)) {
			$host = $matches[1];
			if (isset($matches[2])) {
				$port = (int) substr($matches[2], 1);
			}
		} else {
			$pos = strpos($host, ':');
			if ($pos !== false) {
				$port = (int) substr($host, $pos + 1);
				$host = strstr($host, ':', true);
			}
		}
		$path = parse_url('https://localhost' . ($globals['PATH_INFO'] ?? $globals['REQUEST_URI'] ?? ''), PHP_URL_PATH);
		$path = rawurldecode($path);

		// Query string
		$query = $globals['QUERY_STRING'] ?? parse_url('https://localhost' . $globals['REQUEST_URI'], PHP_URL_QUERY);

		// Fragment
		$fragment = '';

		return new Uri($scheme, $host ?: '', $port, $path, $query ?: '', $fragment, $username, $password);
	}

}