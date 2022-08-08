<?php

namespace Polaris\Socket;

use Polaris\Pool\Manager;

/**
 *
 */
final class Persist extends Socket
{

	/**
	 * @var string
	 */
	protected string $scheme;

	/**
	 * @var string
	 */
	protected string $host;

	/**
	 * @var integer
	 */
	protected int $port;

	/**
	 * @var array
	 */
	protected array $options = [];

	/**
	 * @param resource $resource
	 * @param array $options
	 */
	public function __construct($resource = null, array $options = [])
	{
		parent::__construct($resource, array_merge([
			'size' => 32,
		], $options));
	}

	/**
	 * @param int $domain
	 * @param int $type
	 * @param int $protocol
	 * @return static
	 */
	public function create(int $domain, int $type, int $protocol): self
	{
		$this->domain = $domain;
		$this->type = $type;
		$this->protocol = $protocol;
		foreach (self::$schemes as $scheme => $options) {
			if ($options[0] == $domain && $options[1] == $type && $options[2] == $protocol) {
				$this->scheme = $scheme;
			}
		}
		return $this;
	}

	/**
	 * @param mixed $timeout
	 * @return static
	 */
	public function setTimeout($timeout): self
	{
		$this->options['timeout'] = $timeout;
		return $this;
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @return static
	 * @throws \Polaris\Exception
	 */
	public function connect(string $host, int $port = 0): self
	{
		$this->host = $host;
		$this->port = $port;
		try {
			return parent::connect($host, $port);
		} catch (Exception $e) {
			if (in_array($e->getCode(), [106])) {
				return $this;
			}
			throw $e;
		}
	}

	/**
	 *
	 * @return static
	 * @throws \Polaris\Exception
	 */
	public function close(): self
	{
		if ($this->resource) {
			try {
				if (!Manager::has($this->getName()) || $this->resource->errCode || (func_num_args() > 0 && func_get_arg(0) === false)) {
					$this->resource->close();
					$this->resource = null;
				}
				if (Manager::has($this->getName())) {
					Manager::get($this->getName())->push($this->resource);
				}
				$this->resource = false;
			} catch (\Polaris\Pool\Exception $e) {
				throw new Exception($this, $e->getMessage(), $e->getCode(), $e);
			}
		}
		return $this;
	}

	/**
	 * @return mixed
	 * @throws \Polaris\Exception
	 */
	public function getResource()
	{
		if ($this->resource === false) {
			throw Exception::createFromCode(SOCKET_ENOTSOCK, $this);
		}
		if ($this->resource) {
			return $this->resource;
		}
		try {
			$pool = Manager::has($this->getName()) ? Manager::get($this->getName()) : null;
			if (!$pool) {
				$domain = $this->domain;
				$type = $this->type;
				$protocol = $this->protocol;
				$pool = Manager::create($this->getName(), function () use ($domain, $type, $protocol) {
					return new \Swoole\Coroutine\Socket($domain, $type, $protocol);
				}, $this->options['size'], 0.1, function ($resource) {
					/**
					 * @var \Swoole\Coroutine\Socket $resource
					 */
					$resource->close();
				});
			}
			return $this->resource = $pool->pop();
		} catch (\Polaris\Exception $e) {
			throw $e;
		} catch (\Throwable $e) {
			throw new Exception($this, $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @return string
	 */
	private function getName(): string
	{
		return sprintf('%s://%s:%d', $this->scheme, $this->host, $this->port);
	}

	/**
	 * @param string $scheme
	 * @param array $options
	 * @return Socket
	 * @throws Exception
	 */
	public static function createSocket(string $scheme, array $options = []): Socket
	{
		if (!Manager::available()) {
			//@todo down level to spl queue
			return parent::createSocket($scheme);
		}
		if (!isset(self::$schemes[$scheme])) {
			throw Exception::createFromCode(SOCKET_ESOCKTNOSUPPORT);
		}
		return (new self(null, $options))
			->create(...self::$schemes[$scheme]);
	}



}