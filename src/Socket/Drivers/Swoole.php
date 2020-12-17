<?php

namespace Polaris\Socket\Drivers;

use Polaris\Socket\Exception\SocketException;
use Polaris\Socket\Socket;
use Polaris\Socket\Traits\FormatAddressTrait;

/**
 * Class Swoole
 * @package Polaris\Socket\Drivers
 */
class Swoole implements Socket
{

	use FormatAddressTrait;

	/**
	 * @var \Swoole\Coroutine\Socket
	 */
	protected $socket;

	/**
	 * @var int
	 */
	protected $timeout = 3;

	/**
	 * Swoole constructor.
	 * @param \Swoole\Coroutine\Socket $socket
	 */
	public function __construct(\Swoole\Coroutine\Socket $socket = null)
	{
		$this->socket = $socket;
	}

	/**
	 * @param int $domain
	 * @param int $type
	 * @param int $protocol
	 * @return static
	 */
	public function create($domain, $type, $protocol)
	{
		$this->socket = new \Swoole\Coroutine\Socket($domain, $type, $protocol);
		return $this;
	}

	/**
	 * @return static
	 */
	public function accept()
	{
		$socket = $this->socket->accept($this->timeout);
		return $socket ? new static($socket) : $socket;
	}

	/**
	 * @param string $address
	 * @return static
	 * @throws SocketException
	 */
	public function bind($address)
	{
		if ($this->socket->bind($this->unformatAddress($address, $port), $port) === false) {
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
		if ($this->socket->connect($this->unformatAddress($address, $port), $port, $this->timeout) === false) {
			throw $this->createSocketException();
		}
		return $this;
	}

	/**
	 * @return static
	 * @throws SocketException
	 */
	public function listen()
	{
		if ($this->socket->listen() == false) {
			throw $this->createSocketException();
		}
		return $this;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->socket->getOption(SOL_SOCKET, SO_TYPE);
	}

	/**
	 * @param int $length
	 * @return mixed
	 * @throws SocketException
	 */
	public function read($length)
	{
		return $this->recv($length, 0);
	}

	/**
	 * @param int $length
	 * @param int $flags
	 * @return mixed
	 * @throws SocketException
	 */
	public function recv($length, $flags)
	{
		$buff = $this->socket->recv($length, $this->timeout);
		if ($buff === false) {
			throw $this->createSocketException();
		}
		return $buff;
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
		$buff = $this->socket->recvfrom($peer, $this->timeout);
		if ($buff === false) {
			throw $this->createSocketException();
		}
		$remote = $this->formatAddress(isset($peer['address']) ? $peer['address'] : null, isset($peer['port']) ? $peer['port'] : null);
		return $buff;
	}

	/**
	 * @param string $buffer
	 * @return mixed
	 * @throws SocketException
	 */
	public function write($buffer)
	{
		return $this->send($buffer, 0);
	}

	/**
	 * @param string $buffer
	 * @param int $flags
	 * @return mixed
	 * @throws SocketException
	 */
	public function send($buffer, $flags)
	{
		$result = $this->socket->send($buffer, $this->timeout);
		if ($result === false) {
			throw $this->createSocketException();
		}
		return $result;
	}

	/**
	 * @param string $buffer
	 * @param int $flags
	 * @param string $remote
	 * @return mixed
	 * @throws SocketException
	 */
	public function sendTo($buffer, $flags, $remote)
	{
		$result = $this->socket->sendto($this->unformatAddress($remote, $port), $port, $buffer);
		if ($result === false) {
			throw $this->createSocketException();
		}
		return $result;
	}

	/**
	 * @return static
	 */
	public function shutdown()
	{
		return $this;
	}

	/**
	 * @return static
	 */
	public function close()
	{
		$this->socket->close();
		return $this;
	}

	/**
	 * @return string
	 * @throws SocketException
	 */
	public function localAddress()
	{
		$local = $this->socket->getsockname();
		if ($local === false) {
			throw $this->createSocketException();
		}
		return $this->formatAddress(isset($local['address']) ? $local['address'] : null, isset($local['port']) ? $local['port'] : null);
	}

	/**
	 * @return string
	 * @throws SocketException
	 */
	public function remoteAddress()
	{
		$remote = $this->socket->getsockname();
		if ($remote === false) {
			throw $this->createSocketException();
		}
		return $this->formatAddress(isset($remote['address']) ? $remote['address'] : null, isset($remote['port']) ? $remote['port'] : null);
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
	 * @return SocketException
	 */
	protected function createSocketException()
	{
		return new SocketException($this, socket_strerror($this->socket->errCode), $this->socket->errCode);
	}

}
