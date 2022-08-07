<?php

namespace Polaris\Socket;

use Polaris\Socket\Exception\BrokenPipeException;
use Polaris\Socket\Exception\TimeoutException;
use Throwable;

/**
 *
 */
class Exception extends \Polaris\Exception
{

	/**
	 * @var Socket
	 */
	protected Socket $socket;

	/**
	 * @param Socket $socket
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(Socket $socket, $message = '', $code = 0, Throwable $previous = null)
	{
		$this->socket = $socket;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @param integer $code
	 * @param Socket|null $socket
	 * @return static
	 * @throws Exception
	 */
	public static function createFromCode(int $code, Socket $socket = null): self
	{
		switch ($code) {
			case SOCKET_EAGAIN://noblock try again (coroutine io timeout)
			case SOCKET_ETIMEDOUT://Connection timed out.
			case 10060://windows
				throw new TimeoutException($socket, socket_strerror($code), $code);
			case SOCKET_EPIPE:
				throw new BrokenPipeException($socket, socket_strerror($code), $code);
			default:
				return new static($socket, socket_strerror($code), $code);
		}
	}

}