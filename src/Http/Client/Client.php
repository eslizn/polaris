<?php

namespace Polaris\Http\Client;

use Http\Client\Exception\HttpException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Client\Promise\HttpFulfilledPromise;
use Http\Client\Promise\HttpRejectedPromise;
use Polaris\Http\Body;
use Polaris\Http\Client\Exception\TimeoutException;
use Polaris\Http\Headers;
use Polaris\Http\Response;
use Polaris\Pool\Manager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 *
 */
class Client implements LoggerAwareInterface, HttpAsyncClient, HttpClient, \ArrayAccess
{

    use LoggerAwareTrait;

    /**
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container = null;

    /**
     * @var array
     */
    protected array $options = [];

    /**
     * @param ContainerInterface|null $container
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->options = [
            'timeout' => 3,
            'proxy' => $this->getDefaultProxy(),
        ];
        $this->setLogger($container && $container->has(LoggerInterface::class) ?
            $container->get(LoggerInterface::class) : new NullLogger());
    }

    /**
     * @return array|null
     * @throws Exception
     */
    private function getDefaultProxy(): ?array
    {
        if (!empty(getenv('HTTP_PROXY'))) {
            $parsed = parse_url(getenv('HTTP_PROXY'));
            if (!isset($parsed['port'])) {
                switch (strtolower($parsed['scheme'])) {
                    case 'http':
                        $parsed['port'] = 80;
                        break;
                    case 'https':
                        $parsed['port'] = 443;
                        break;
                    default:
                        throw new Exception(null, 'proxy missing port', -__LINE__);
                }
            }
            return [
                'scheme' => $parsed['scheme'] ?? 'http',
                'host' => trim($parsed['host'], ':'),
                'port' => intval($parsed['port']),
                'user' => $parsed['user'] ?? '',
                'pass' => $parsed['pass'] ?? '',
            ];
        }
        return null;
    }

    /**
     *
     * @throws \Polaris\Exception
     */
    private function handleSwoole(RequestInterface $request): Response
    {
        $ssl = $request->getUri()->getScheme() === 'https';
        $host = $request->getUri()->getHost();
        $port = intval($request->getUri()->getPort()) ?: ($ssl ? 443 : 80);
        $name = strval($request->getUri()
            ->withPath('/')
            ->withQuery('')
            ->withFragment(''));
        $factory = function () use ($host, $port, $ssl) {
            return new \Swoole\Coroutine\Http\Client($host, $port, $ssl);
        };
        $pool = null;
        try {
            if (Manager::available()) {
                if (!Manager::has($name)) {
                    Manager::create($name, $factory);
                }
                $pool = Manager::get($name);
            }
            $client = $pool ? $pool->pop() : $factory();
        } catch (\Polaris\Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception($request, $e->getMessage(), $e->getCode(), $e);
        }
        $options = $this->options;
        if ($this->options['proxy']) {
            $options['http_proxy_host'] = $this->options['proxy']['host'];
            $options['http_proxy_port'] = $this->options['proxy']['port'];
            if ($this->options['proxy']['user']) {
                $options['http_proxy_user'] = $this->options['proxy']['user'];
            }
            if ($this->options['proxy']['pass']) {
                $options['http_proxy_password'] = $this->options['proxy']['pass'];
            }
        }
        $client->set(array_only($options, [
            'timeout',
            'ssl_cert_file',
            'ssl_key_file',
            'ssl_verify_peer',
            'ssl_allow_self_signed',
            'ssl_host_name',
            'ssl_cafile',
            'ssl_capath',
            'ssl_passphrase',
            'socks5_host',
            'socks5_port',
            'socks5_username',
            'socks5_password',
            'http_proxy_host',
            'http_proxy_port',
            'bind_address',
            'bind_port',
        ]));
        $client->setMethod($request->getMethod());
        $client->setCookies($request->getCookieParams() ?: []);
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[$name] = implode(',', $values);
        }
        $client->setHeaders($headers);
        if ($request->getUploadedFiles()) {
            foreach ($request->getUploadedFiles() as $file) {
                $client->addFile($file->file, $file->getClientFilename(), $file->getClientMediaType(), $file->getClientFilename());
            }
            parse_str($request->getBody() ? $request->getBody()->getContents() : '', $fields);
            if ($fields) {
                $client->setData($fields);
            }
        } else {
            $client->setData($request->getBody() ? $request->getBody()->getContents() : null);
        }
        try {
            $client->execute($request->getUri()->getPath() . '?' . $request->getUri()->getQuery());
            if ($client->errCode) {
                switch ($client->errCode) {
                    case 110:
                        throw new TimeoutException($request, socket_strerror($client->errCode), $client->errCode);
                    default:
                        throw new Exception($request, socket_strerror($client->errCode), $client->errCode);
                }
            }
            return new Response($client->statusCode, new Headers($client->headers), new Body($client->body));
        } catch (\Polaris\Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception($request, $e->getMessage(), $e->getCode(), $e);
        } finally {
            if (isset($e)) {
                $client->close();
                $client = null;
            }
            if ($pool) {
                try {
                    $pool->push($client);
                } catch (Throwable $e) {
                    throw new Exception($request, $e->getMessage(), $e->getCode(), $e);
                }
            }
        }
    }

    /**
     * @throws \Polaris\Exception
     */
    private function handleCurl(RequestInterface $request): Response
    {
        try {
            $handle = curl_init();
            $body = $request->getBody();
            //fix curl not support set host header
            $host = $request->getHeaderLine('Host');
            if ($host && strcmp($host, $request->getUri()->getHost())) {
                curl_setopt($handle, CURLOPT_RESOLVE, [sprintf('%s:%d:%s', $host, $request->getUri()->getPort(), $request->getUri()->getHost())]);
                $request = $request->withUri($request->getUri()->withHost($host), true);
            }
            curl_setopt($handle, CURLOPT_PROXY, getenv('HTTP_PROXY') ?? null);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_URL, strval($request->getUri()));
            curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $request->getMethod());
            curl_setopt($handle, CURLOPT_HEADER, true);
            curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);//@todo
            if ($this->options['proxy']) {
                curl_setopt($handle, CURLOPT_PROXY, $this->options['proxy']['host']);
                curl_setopt($handle, CURLOPT_PROXYPORT, $this->options['proxy']['port']);
                if ($this->options['proxy']['user']) {
                    curl_setopt($handle, CURLOPT_PROXYUSERPWD, $this->options['proxy']['user'] . ':' . $this->options['proxy']['pass']);
                }
                curl_setopt($handle, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }

            if (isset($this->options['timeout'])) {
                curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, intval($this->options['timeout']));
                curl_setopt($handle, CURLOPT_TIMEOUT, intval($this->options['timeout']));
            }
            if ($request->getUploadedFiles()) {
                parse_str($body ? $body->getContents() : '', $body);
                foreach ($request->getUploadedFiles() as $file) {
                    $body[$file->getClientFilename()] = curl_file_create($file->file, $file->getClientMediaType(), $file->getClientFilename());
                }
            }

            if ($request->getHeaders()) {
                $headers = [];
                foreach ($request->getHeaders() as $name => $values) {
                    $headers[] = $name . ': ' . implode(',', $values);
                }
                curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
            }
            if (!empty($body)) {
                curl_setopt($handle, CURLOPT_POSTFIELDS, $body instanceof StreamInterface ? $body->getContents() : $body);
            }
            $response = curl_exec($handle);
            if ($response === false) {
                switch (curl_errno($handle)) {
                    case 28:
                        throw new TimeoutException($request,  curl_error($handle), curl_errno($handle));
                    default:
                        throw new Exception($request, curl_error($handle), curl_errno($handle));
                }
            }
            $heads = substr($response, 0, curl_getinfo($handle, CURLINFO_HEADER_SIZE));
            $body = substr($response, strlen($heads));
            $headers = [];
            foreach (explode("\n", $heads) as $head) {
                $position = strpos($head, ':');
                if (!$position) {
                    continue;
                }
                $headers[trim(substr($head, 0, $position))] = trim(substr($head, $position+1));
            }
            return new Response(curl_getinfo($handle, CURLINFO_HTTP_CODE), new Headers($headers), new Body($body));
        } catch (\Polaris\Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new Exception($request, $e->getMessage(), $e->getCode(), $e);
        } finally {
            curl_close($handle);
        }
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->logger->info('[Method]' . $request->getMethod(), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
        $this->logger->info( '[Uri]' . $request->getUri(), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
        $this->logger->info('[Headers]' . json_encode($request->getHeaders()), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
        if ($request->getBody()) {
            $this->logger->info('[Body]' . $request->getBody()->getContents(), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
            $request->getBody()->rewind();
        }
        try {
            if (Manager::available()) {
                $response = $this->handleSwoole($request);
            } else {
                $response = $this->handleCurl($request);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage(), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
            throw new Exception($request, $e->getMessage(), $e->getCode(), $e);
        }
        $this->logger->info('[Status]' . $response->getStatusCode(), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
        $this->logger->info('[Headers]' . json_encode($response->getHeaders()), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
        if ($response->getBody()) {
            $this->logger->info('[Body]' . $response->getBody()->getContents(), ['file' => __FILE__, 'line' => __LINE__, 'module' => 'HttpClient']);
            $response->getBody()->rewind();
        }
        return $response;
    }

    /**
     * @param RequestInterface $request
     * @return HttpFulfilledPromise|HttpRejectedPromise
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $response = null;
        try {
            $response = $this->sendRequest($request);
            return new HttpFulfilledPromise($response);
        } catch (Throwable $e) {
            return new HttpRejectedPromise(new HttpException($e->getMessage(), $e->getCode(), $response));
        }
    }

    /**
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->options[$offset]);
    }

    /**
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->options[$offset] ?? null;
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->options[$offset] = $value;
    }

    /**
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->options[$offset]);
    }

}