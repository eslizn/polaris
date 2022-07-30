<?php

namespace Polaris\Http;

use Psr\Http\Message\UriInterface;

/**
 *
 */
class Uri implements UriInterface
{

    /**
     * Uri scheme (without "://" suffix)
     *
     * @var string|null
     */
    protected ?string $scheme = '';

    /**
     * Uri user
     *
     * @var string|null
     */
    protected ?string $user = '';

    /**
     * Uri password
     *
     * @var string|null
     */
    protected ?string $password = '';

    /**
     * Uri host
     *
     * @var string|null
     */
    protected ?string $host = '';

    /**
     * Uri port number
     *
     * @var null|int
     */
    protected ?int $port = null;

    /**
     * Uri path
     *
     * @var string
     */
    protected string $path = '';

    /**
     * Uri query string (without "?" prefix)
     *
     * @var string
     */
    protected string $query = '';

    /**
     * Uri fragment string (without "#" prefix)
     *
     * @var string
     */
    protected string $fragment = '';

    /**
     * Create new Uri from string.
     *
     * @param string $uri Complete Uri string
     *     (i.e., https://user:pass@host:443/path?query).
     *
     * @return static
     */
    public static function createFromString(string $uri): self
    {
        $parts = parse_url($uri);
        return new static(
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
     * @param \Swoole\Http\Request $request
     * @return static
     */
    public static function createFromSwoole(\Swoole\Http\Request $request): self
    {
        $host = $request->header['host'] ?? '127.0.0.1';
        $port = $request->server['server_port'] ?? 80;
        $last = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? false : strrpos($host, ':');
        return new static(
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
     * @return static
     */
    public static function createFromGlobals(array $globals): self
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

        return new static($scheme, $host ?: '', $port, $path, $query, $fragment, $username, $password);
    }

    /**
     * Create new Uri.
     *
     * @param string|null $scheme Uri scheme.
     * @param string|null $host Uri host.
     * @param int|null $port Uri port number.
     * @param string $path Uri path.
     * @param string $query Uri query string.
     * @param string $fragment Uri fragment.
     * @param string $user Uri user.
     * @param string $password Uri password.
     */
    public function __construct(
        ?string $scheme,
        ?string $host,
        ?int    $port = null,
        string  $path = '/',
        string  $query = '',
        string  $fragment = '',
        string  $user = '',
        string $password = ''
    ) {
        $this->scheme = $scheme ?: '';
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getAuthority(): string
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();
        return ($userInfo ? $userInfo . '@' : '') . $host . ($port !== null ? ':' . $port : '');
    }

    /**
     * @return string
     */
    public function getUserInfo(): string
    {
        return $this->user . ($this->password ? ':' . $this->password : '');
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host ?: '';
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @param string $scheme
     * @return static
     */
    public function withScheme($scheme): self
    {
        $clone = clone $this;
        $clone->scheme = $scheme;
        return $clone;
    }

    /**
     * @param string $user
     * @param string|null $password
     * @return static
     */
    public function withUserInfo($user, $password = null): self
    {
        $clone = clone $this;
        $clone->user = $user;
        $clone->password = $password;
        return $clone;
    }

    /**
     * @param string $host
     * @return static
     */
    public function withHost($host): self
    {
        $clone = clone $this;
        $clone->host = $host;
        return $clone;
    }

    /**
     * @param int|null $port
     * @return static
     */
    public function withPort($port): self
    {
        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }

    /**
     * @param string $path
     * @return static
     */
    public function withPath($path): self
    {
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    /**
     * @param string $query
     * @return static
     */
    public function withQuery($query): self
    {
        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    /**
     * @param string $fragment
     * @return static
     */
    public function withFragment($fragment): self
    {
        $clone = clone $this;
        $clone->fragment = $fragment;
        return $clone;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();
        $path = '/' . ltrim($path, '/');
        return ($scheme ? $scheme . ':' : '')
            . ($authority ? '//' . $authority : '')
            . $path
            . ($query ? '?' . $query : '')
            . ($fragment ? '#' . $fragment : '');
    }

}