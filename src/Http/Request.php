<?php

namespace Polaris\Http;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 *
 */
class Request extends Message implements ServerRequestInterface
{

    /**
     * @var string
     */
    protected string $method;

    /**
     * @var UriInterface
     */
    protected UriInterface $uri;

    /**
     * @var string|null
     */
    protected ?string $target = null;

    /**
     * @var array|null
     */
    protected ?array $query = null;

    /**
     * @var Cookies
     */
    protected Cookies $cookies;

    /**
     * @var array
     */
    protected array $server = [];

    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * @var mixed
     */
    protected $parsed = false;

    /**
     * @var array
     */
    protected array $parsers = [];

    /**
     * @var UploadedFileInterface[]
     */
    protected array $uploaded = [];

    /**
     * Create new HTTP request with data extracted from Swoole Request
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
        $method = $request->server['request_method'] ?? 'GET';
        $req = new static(
            $method,
            Uri::createFromSwoole($request),
            Headers::createFromSwoole($request),
            new Cookies($request->cookie ?: []),
            array_change_key_case($request->server, CASE_UPPER),
            new Body($request->rawContent()),
            $request->files ? UploadedFile::parseUploadedFiles($request->files) : []
        );
        if ($request->post) {
            $req = $req->withParsedBody($request->post);
        }

        return $req->withAttribute(\Swoole\Http\Request::class, $request);
    }

    /**
     * Create new HTTP request with data extracted from the application
     * Environment object
     *
     * @param array $globals The global server variables.
     *
     * @return static
     * @throws Exception
     */
    public static function createFromGlobals(array $globals): self
    {
        $method = $globals['REQUEST_METHOD'] ?? null;
        $uri = Uri::createFromGlobals($globals);
        $headers = Headers::createFromGlobals($globals);
        $cookies = Cookies::parse(current($headers->get('Cookie', [])));
        $uploadedFiles = UploadedFile::parseUploadedFiles($_FILES);
        $request = new static($method, $uri, $headers, $cookies, $globals, new Body(), $uploadedFiles);

        if (is_array($_POST) && $_POST) {
            $request = $request->withParsedBody($_POST);
        } else if ($headers['Content-Length']) {
            $request = $request->withBody(new Stream(STDIN));
        }
        return $request;
    }

    /**
     * @param string $method
     * @param UriInterface $uri
     * @param Headers|null $headers
     * @param Cookies|null $cookies
     * @param array $servers
     * @param StreamInterface|null $body
     * @param array $uploaded
     */
    public function __construct(
        string          $method,
        UriInterface    $uri,
        ?Headers        $headers = null,
        ?Cookies        $cookies = null,
        array           $servers = [],
        StreamInterface $body = null,
        array           $uploaded = []
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->headers = $headers ?: new Headers();
        $this->cookies = $cookies ?: new Cookies();
        $this->server = $servers;
        $this->attributes = [];
        $this->body = $body ?: new Body();
        $this->uploaded = $uploaded;

        if (isset($servers['SERVER_PROTOCOL'])) {
            $this->version = str_replace('HTTP/', '', $servers['SERVER_PROTOCOL']);
        }

        if (!$this->headers['Host'] || !empty($this->uri->getHost())) {
            $this->headers['Host'] = $uri->getHost();
        }

        if (function_exists('json_decode')) {
            $this->register('application/json', function ($input) {
                $result = json_decode($input, true);
                if (!is_array($result)) {
                    return null;
                }
                return $result;
            });
        }

        if (function_exists('libxml_disable_entity_loader')) {
            $this->register('application/xml', function ($input) {
                $backup = libxml_disable_entity_loader(true);
                $backup_errors = libxml_use_internal_errors(true);
                $result = simplexml_load_string($input);
                libxml_disable_entity_loader($backup);
                libxml_clear_errors();
                libxml_use_internal_errors($backup_errors);
                if ($result === false) {
                    return null;
                }
                return $result;
            });
        }

        if (function_exists('libxml_disable_entity_loader')) {
            $this->register('text/xml', function ($input) {
                $backup = libxml_disable_entity_loader(true);
                $backup_errors = libxml_use_internal_errors(true);
                $result = simplexml_load_string($input);
                libxml_disable_entity_loader($backup);
                libxml_clear_errors();
                libxml_use_internal_errors($backup_errors);
                if ($result === false) {
                    return null;
                }
                return $result;
            });
        }

        $this->register('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        if (!is_null($this->target)) {
            return $this->target;
        }
        return strval($this->uri->withScheme(null)
            ->withHost(null)
            ->withPath(null));
    }

    /**
     * @param string $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget): self
    {
        $clone = clone $this;
        $clone->target = $requestTarget;
        return $clone;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     * @return static
     */
    public function withMethod($method): self
    {
        $clone = clone $this;
        $this->method = $method;
        return $clone;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }


    /**
     * @param UriInterface $uri
     * @param bool $preserveHost
     * @return $this
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $clone = clone $this;
        $clone->uri = $uri;
        if (!empty($uri->getHost())) {
            if (!$preserveHost || !$this->hasHeader('Host') || empty($this->getHeaderLine('Host'))) {
                $clone->headers['Host'] = $uri->getHost();
            }
        }
        return $clone;
    }

    /**
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->cookies->cookies;
    }

    /**
     * @param array $cookies
     * @return static
     */
    public function withCookieParams(array $cookies): self
    {
        $clone = clone $this;
        foreach ($clone->cookies as $k => $v) {
            unset($clone->cookies[$k]);
        }
        foreach ($cookies as $k => $v) {
            $clone->cookies[$k] = $v;
        }
        return $clone;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        if (is_null($this->query)) {
            parse_str($this->uri->getQuery(), $this->query);
        }
        return $this->query ?: [];
    }

    /**
     * @param array $query
     * @return static
     */
    public function withQueryParams(array $query): self
    {
        $clone = clone $this;
        $clone->query = $query;
        return $clone;
    }

    /**
     * @return UploadedFileInterface[]
     */
    public function getUploadedFiles(): array
    {
        return $this->uploaded;
    }

    /**
     * @param array $uploadedFiles
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        $clone = clone $this;
        $clone->uploaded = $uploadedFiles;
        return $clone;
    }


    /**
     * @return array|bool|mixed|object|null
     * @throws Exception
     */
    public function getParsedBody()
    {
        if ($this->parsed !== false) {
            return $this->parsed;
        }
        if (!$this->body) {
            return null;
        }
        $mediaType = $this->getMediaType();

        // look for a media type with a structured syntax suffix (RFC 6839)
        $parts = explode('+', $mediaType);
        if (count($parts) >= 2) {
            $mediaType = 'application/' . $parts[count($parts)-1];
        }

        if (isset($this->parsers[$mediaType]) === true) {
            $body = (string)$this->getBody();
            $parsed = $this->parsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new Exception('Request body media type parser return value must be an array, an object, or null', -__LINE__);
            }
            $this->parsed = $parsed;
            return $this->parsed;
        }

        return null;
    }

    /**
     * @param mixed $data
     * @return static
     * @throws Exception
     */
    public function withParsedBody($data): self
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new Exception('Parsed body value must be an array, an object, or null', -__LINE__);
        }
        $clone = clone $this;
        $clone->parsed = $data;
        return $clone;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function withAttribute($name, $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * @param string $name
     * @return static
     */
    public function withoutAttribute($name): self
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return current($this->getHeader('Content-Type') ?: [null]);
    }

    /**
     * @return string|null
     */
    public function getMediaType(): ?string
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            return strtolower($contentTypeParts[0]);
        }
        return null;
    }

    /**
     * Get request media type params, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return array
     */
    public function getMediaTypeParams(): array
    {
        $contentType = $this->getContentType();
        $contentTypeParams = [];
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            $contentTypePartsLength = count($contentTypeParts);
            for ($i = 1; $i < $contentTypePartsLength; $i++) {
                $paramParts = explode('=', $contentTypeParts[$i]);
                $contentTypeParams[strtolower($paramParts[0])] = $paramParts[1];
            }
        }

        return $contentTypeParams;
    }

    /**
     * Does this request use a given method?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param  string|null $method HTTP method
     * @return bool
     */
    public function isMethod(?string $method): bool
    {
        return $this->getMethod() === $method;
    }

    /**
     * Is this a GET request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Is this a POST request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Is this a PUT request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    /**
     * Is this a PATCH request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Is this a DELETE request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    /**
     * Is this a HEAD request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    /**
     * Is this a OPTIONS request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    /**
     * Is this an XHR request?
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    public function isXhr(): bool
    {
        return $this->getHeaderLine('X-RequestedEvent-With') === 'XMLHttpRequest' ||
            $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }


    /**
     * Get request content character set, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return string|null
     */
    public function getContentCharset(): ?string
    {
        $mediaTypeParams = $this->getMediaTypeParams();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }

        return null;
    }

    /**
     * Get request content length, if known.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return int|null
     */
    public function getContentLength(): ?int
    {
        $result = $this->headers['Content-Length'];
        return $result ? intval(current($result)) : null;
    }

    /**
     * @param string $type
     * @param callable $callable
     * @return static
     */
    public function register(string $type, callable $callable): self
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->parsers[$type] = $callable;
        return $this;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
        $this->cookies = clone $this->cookies;
        $this->body = clone $this->body;
    }


}