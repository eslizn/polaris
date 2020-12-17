<?php

namespace Polaris\Socket\Exception;

use Polaris\Socket\Socket;

/**
 * Class ConnectException
 * @package Polaris\Socket\Exception
 */
class SocketException extends Exception
{

	/**
	 * @var Socket
	 */
	protected $socket;

	/**
	 * ConnectException constructor.
	 * @param Socket $socket
	 * @param string $message
	 * @param int $code
	 */
	public function __construct(Socket $socket, $message, $code)
	{
		$this->socket = $socket;
		parent::__construct($message, $code);
	}

}