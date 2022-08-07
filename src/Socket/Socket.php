<?php

namespace Polaris\Socket;

/**
 *
 */
class Socket
{

	/**
	 *
	 */
	const SelectRead = 1;

	/**
	 *
	 */
	const SelectWrite = 2;

	/**
	 *
	 */
	const SelectExcept = 4;

	/**
	 * @var array
	 */
	protected static array $schemes = [
		'tcp'	=> [AF_INET, SOCK_STREAM, SOL_TCP],
		'tcp6'	=> [AF_INET6, SOCK_STREAM, SOL_TCP],
		'udp'	=> [AF_INET, SOCK_DGRAM, SOL_UDP],
		'udp6'	=> [AF_INET6, SOCK_DGRAM, SOL_UDP],
		//'icmp'	=> [AF_INET, SOCK_RAW, getprotobyname('icmp')],
		//'icmp6'	=> [AF_INET6, SOCK_RAW, getprotobyname('ipv6-icmp')],
		'unix'	=> [AF_UNIX, SOCK_STREAM, 0],
		'udg'	=> [AF_UNIX, SOCK_DGRAM, 0],
	];

	/**
	 * @var integer
	 */
	protected int $domain;

	/**
	 * @var integer
	 */
	protected int $type;

	/**
	 * @var integer
	 */
	protected int $protocol;

	/**
	 * @var resource
	 */
	protected $resource;

	/**
	 * @var float
	 */
	protected $timeout = 3;

	/**
	 * Socket constructor.
	 * @param mixed $resource
	 */
	public function __construct($resource = null)
	{
		$this->resource = $resource;
	}

	/**
	 * @param int $domain
	 * @param int $type
	 * @param int $protocol
	 * @return static
	 * @throws Exception
	 */
	public function create(int $domain, int $type, int $protocol): self
	{
		$this->domain = $domain;
		$this->type = $type;
		$this->protocol = $protocol;
		if (static::inCoroutine()) {
			try {
				$this->resource = new \Swoole\Coroutine\Socket($domain, $type, $protocol);
			} catch (\Swoole\Coroutine\Socket\Exception $e) {
				throw Exception::createFromCode($e->getCode(), $this);
			}
		} else {
			$this->resource = @socket_create($domain, $type, $protocol);
			if (!$this->resource) {
				throw Exception::createFromCode(socket_last_error($this->resource), $this);
			}
		}
		return $this;
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @return static
	 * @throws Exception
	 */
	public function bind(string $host, int $port = 0): self
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			if (!$socket->bind($host, $port)) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			if (!@socket_bind($this->resource, $host, $port)) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $this;
	}

	/**
	 * @param int $backlog
	 * @return static
	 * @throws Exception
	 */
	public function listen(int $backlog = 0): self
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			if (!$socket->listen($backlog)) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			if (!@socket_listen($socket, $backlog)) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $this;
	}

	/**
	 * @return static
	 * @throws Exception
	 */
	public function accept(): self
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$resource = $socket->accept($this->timeout);
			if (!$resource) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			$resource = @socket_accept($socket);
			if (!$resource) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return new static($resource);
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @return static
	 * @throws Exception
	 */
	public function connect(string $host, int $port = 0): self
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			if (!$socket->connect($host, $port, $this->timeout)) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			if (!@socket_connect($socket, $host, $port)) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $this;
	}

	/**
	 * @param integer $length
	 * @param integer $mode
	 * @return string
	 * @throws Exception
	 */
	public function read(int $length, int $mode = PHP_BINARY_READ): string
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$buffer = $socket->recv($length, $this->timeout);
			if ($buffer === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			$buffer = @socket_read($socket, $length, $mode);
			if ($buffer === false) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $buffer;
	}

	/**
	 * @param int $length
	 * @param int $flags
	 * @return string
	 * @throws Exception
	 */
	public function recv(int $length, int $flags = MSG_WAITALL): string
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$buffer = $socket->recvAll($length, $this->timeout);
			if ($buffer === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			if (@socket_recv($socket, $buffer, $length, $flags) === false) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $buffer;
	}

	/**
	 * @param int $length
	 * @param int $flags
	 * @param string|null $host
	 * @param int $port
	 * @return string
	 * @throws Exception
	 */
	public function recvFrom(int $length, int $flags, string &$host = null, int &$port = 0): string
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$peer = [];
			$buffer = $socket->recvFrom($peer, $this->timeout)->recvfrom();
			$host = $peer['address'] ?? null;
			$port = $peer['port'] ?? 0;
			if ($buffer === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			if (@socket_recvfrom($socket, $buffer, $length, $flags, $host, $port) === false) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $buffer;
	}

	/**
	 * @param string $buffer
	 * @return integer
	 * @throws Exception
	 */
	public function write(string $buffer): int
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$result = $socket->send($buffer, $this->timeout);
			if ($result === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			$result = @socket_write($socket, $buffer, strlen($buffer));
			if ($result === false) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $result;
	}

	/**
	 * @param string $buffer
	 * @param int $flags
	 * @return int
	 * @throws Exception
	 */
	public function send(string $buffer, int $flags = 0): int
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$result = $socket->sendAll($buffer, $this->timeout);
			if ($result === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
			return $result;
		} else {
			$completed = 0;
			do {
				$result = @socket_send($socket, substr($buffer, $completed), strlen($buffer) - $completed, $flags);
				if ($result === false) {
					throw Exception::createFromCode(socket_last_error($socket), $this);
				}
				$completed += $result;
			} while ($completed < strlen($buffer));
			return $completed;
		}
	}

	/**
	 * @param string $buffer
	 * @param int $flags
	 * @param string $host
	 * @param int $port
	 * @return int
	 * @throws Exception
	 */
	public function sendTo(string $buffer, int $flags, string $host, int $port = 0): int
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$result = $socket->sendto($host, $port, $buffer);
			if ($result === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			$result = @socket_sendto($socket, $buffer, strlen($buffer), $flags, $host, $port);
			if ($result === false) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $result;
	}

	/**
	 * @return static
	 */
	public function close(): self
	{
		if ($this->resource) {
			if ($this->isCoroutine()) {
				$this->resource->close();
			} else {
				@socket_close($this->resource);
			}
			$this->resource = false;
		}
		return $this;
	}

	/**
	 * @param mixed $timeout
	 * @return static
	 * @throws Exception
	 */
	public function setTimeout($timeout): self
	{
		if (!is_array($timeout)) {
			$timeout = [
				'sec' => is_null($timeout) ? null : intval($timeout),
				'usec' => is_null($timeout) ? null : ($timeout * 1000000 % 1000000),
			];
		}
		$this->timeout = $timeout['sec'] ?? 0 + ($timeout['usec'] ?? 0) / 1000000;
		return $this->setOption(SOL_SOCKET, SO_RCVTIMEO, $timeout)
			->setOption(SOL_SOCKET, SO_SNDTIMEO, $timeout);
	}

	/**
	 * @param int $level
	 * @param int $option
	 * @param mixed $value
	 * @return static
	 * @throws Exception
	 */
	public function setOption(int $level, int $option, $value): self
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			if ($socket->setOption($level, $option, $value) === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			if (@socket_set_option($socket, $level, $option, $value) === false) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $this;
	}

	/**
	 * @param int $level
	 * @param int $option
	 * @return array|int
	 * @throws Exception
	 */
	public function getOption(int $level, int $option)
	{
		$socket = $this->getResource();
		if ($this->isCoroutine()) {
			$result = $socket->getOption($level, $option);
			if ($result === false) {
				throw Exception::createFromCode($socket->errCode, $this);
			}
		} else {
			$result = @socket_get_option($socket, $level, $option);
			if ($result === false) {
				throw Exception::createFromCode(socket_last_error($socket), $this);
			}
		}
		return $result;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	protected function getResource()
	{
		if (!$this->resource) {
			throw Exception::createFromCode(SOCKET_ENOTSOCK, $this);
		}
		return $this->resource;
	}

	/**
	 * @return bool
	 */
	protected static function inCoroutine(): bool
	{
		return class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() > 0;
	}

	/**
	 * @return bool
	 */
	protected function isCoroutine(): bool
	{
		return $this->resource instanceof \Swoole\Coroutine\Socket;
	}

	/**
	 * @param string $scheme
	 * @param array $options
	 * @return Socket
	 * @throws Exception
	 */
	public static function createSocket(string $scheme, array $options = []): self
	{
		if (!isset(static::$schemes[$scheme])) {
			throw Exception::createFromCode(SOCKET_ESOCKTNOSUPPORT);
		}
		return (new static())->create(...static::$schemes[$scheme]);
	}

}