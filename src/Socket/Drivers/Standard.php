<?php

namespace Polaris\Socket\Drivers;

use Polaris\Socket\Exception\SocketException;
use Polaris\Socket\Socket;
use Polaris\Socket\Traits\FormatAddressTrait;

/**
 * Class Socket
 *
 * @package Polaris\Socket\Drivers
 */
class Standard implements Socket
{

	use FormatAddressTrait;

	/**
	 * @var resource
	 */
	protected $resource;

	/**
	 * @var int
	 */
	protected $timeout = 3;

	/**
	 * Standard constructor.
	 *
	 * @param null $resource
	 * @throws SocketException
	 */
	public function __construct($resource = null)
	{
		$this->resource = $resource;
		if ($this->resource && socket_set_nonblock($this->resource) === false) {
			throw $this->createSocketException();
		}
	}

	/**
	 * socket create
	 *
	 * @param integer $domain
	 * @param integer $type
	 * @param integer $protocol
	 * @return static
	 * @throws SocketException
	 */
	public function create($domain, $type, $protocol)
	{
		$this->resource = socket_create($domain, $type, $protocol);
		if ($this->resource === false) {
			throw $this->createSocketException();
		}
		return $this;
	}

	/**
	 * accept listen connect
	 *
	 * @return static|false
	 * @throws SocketException
	 */
	public function accept()
	{
		$resource = socket_accept($this->resource);
		return $resource ? new static($resource) : $resource;
	}

	/**
	 * @param string $address
	 * @return static
	 * @throws SocketException
	 */
	public function bind($address)
	{
		$result = socket_bind($this->resource, $this->unformatAddress($address, $port), $port);
		if ($result === false) {
			throw $this->createSocketException();
		}
		return $this;
	}

	/**
	 * @param string $address
	 * @return static
	 * @throws SocketException
	 */
	public function connect($address)
	{
		if (socket_set_nonblock($this->resource) === false) {
			throw $this->createSocketException();
		}
		if (socket_connect($this->resource, $this->unformatAddress($address, $port), $port) === true) {
			return $this;
		}
		$error = socket_last_error($this->resource);
		if (!in_array($error, [SOCKET_EINPROGRESS, SOCKET_EALREADY, SOCKET_EISCONN])) {// in windows 10035
			throw $this->createSocketException();
		}
		if (!$this->useSelect(false)) {
			throw $this->createSocketException(SOCKET_ETIMEDOUT);
		}
		return $this;
	}

	/**
	 * @return static
	 * @throws SocketException
	 */
	public function listen()
	{
		if (socket_listen($this->resource, 0) === false) {
			throw $this->createSocketException();
		}
		if (socket_set_nonblock($this->resource) === false) {
			throw $this->createSocketException();
		}
		return $this;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return socket_get_option($this->resource, SOL_SOCKET, SO_TYPE);
	}

	/**
	 * @param int $length
	 * @param int $flags
	 * @return mixed
	 * @throws SocketException
	 */
	public function recv($length, $flags)
	{
		$this->useSelect();
		if (socket_recv($this->resource, $buffer, $length, $flags) === false) {
			throw $this->createSocketException();
		}
		return $buffer;
	}

	/**
	 * @param int $length
	 * @param int $flags
	 * @param string $remote
	 * @return mixed
	 * @throws SocketException
	 */
	public function recvFrom($length, $flags, &$remote)
	{
		$this->useSelect();
		if (socket_recvfrom($this->resource, $buffer, $length, $flags, $address, $port) === false) {
			throw $this->createSocketException();
		}
		$remote = $this->formatAddress($address, $port);
		return $buffer;
	}

	/**
	 * @param string $buffer
	 * @param int $flags
	 * @return false|int|mixed
	 * @throws SocketException
	 */
	public function send($buffer, $flags)
	{
		$this->useSelect(false);
		$result = socket_send($this->resource, $buffer, strlen($buffer), $flags);
		if ($result === false) {
			throw $this->createSocketException();
		}
		return $result;
	}

	/**
	 * @param string $buffer
	 * @param int $flags
	 * @param string $remote
	 * @return false|int|mixed
	 * @throws SocketException
	 */
	public function sendTo($buffer, $flags, $remote)
	{
		$this->useSelect(false);
		$result = socket_sendto($this->resource, $buffer, strlen($buffer), $flags, $this->unformatAddress($remote, $port), $port);
		if ($result === false) {
			throw $this->createSocketException();
		}
		return $result;
	}

	/**
	 * @param int $length
	 * @return string
	 * @throws SocketException
	 */
	public function read($length)
	{
		$this->useSelect();
		$buffer = socket_read($this->resource, $length, PHP_BINARY_READ);
		if ($buffer === false) {
			throw $this->createSocketException();
		}
		return $buffer;
	}

	/**
	 * @param string $buffer
	 * @return int
	 * @throws SocketException
	 */
	public function write($buffer)
	{
		$this->useSelect(false);
		$result = socket_write($this->resource, $buffer);
		if ($result === false) {
			throw $this->createSocketException();
		}
		return $result;
	}

	/**
	 * @return static
	 * @throws SocketException
	 */
	public function shutdown()
	{
		if (socket_shutdown($this->resource, 2) === false) {
			throw $this->createSocketException();
		}
		return $this;
	}

	/**
	 * close socket
	 *
	 * @return static
	 */
	public function close()
	{
		socket_close($this->resource);
		return $this;
	}

	/**
	 * @param int $timeout
	 * @return static
	 */
	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
		return $this;
	}

	/**
	 * @return string
	 * @throws SocketException
	 */
	public function localAddress()
	{
		if (socket_getsockname($this->resource, $address, $port) === false) {
			throw $this->createSocketException();
		}
		return $this->formatAddress($address, $port);
	}

	/**
	 * @return string
	 * @throws SocketException
	 */
	public function remoteAddress()
	{
		if (socket_getpeername($this->resource, $address, $port) === false) {
			throw $this->createSocketException();
		}
		return $this->formatAddress($address, $port);
	}

	/**
	 * @param bool $read
	 * @return bool
	 * @throws SocketException
	 */
	protected function useSelect($read = true)
	{
		$args = [[], [], []];
		array_push($args[$read ? 0 : 1], $this->resource);
		$result = socket_select($args[0], $args[1], $args[2], $this->timeout,
			is_null($this->timeout) ? null : (($this->timeout - floor($this->timeout)) * 1000000));
		if ($result === false) {
			throw $this->createSocketException();
		}
		return boolval($result);
	}

	/**
	 * @param mixed $code
	 * @return SocketException
	 */
	protected function createSocketException($code = null)
	{
		if (is_null($code)) {
			$code = socket_last_error($this->resource);
		}
		return new SocketException($this, socket_strerror($code), $code);
	}

}