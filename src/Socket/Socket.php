<?php

namespace Polaris\Socket;

/**
 * Interface Socket
 *
 * @package Polaris\Socket
 */
interface Socket extends Reader, Writer
{

	/**
	 * socket create
	 *
	 * @param integer $domain
	 * @param integer $type
	 * @param integer $protocol
	 * @return static
	 */
	public function create($domain, $type, $protocol);

	/**
	 * accept an incoming connection on this listening socket
	 *
	 * @return static
	 */
	public function accept();

	/**
	 * binds a name/address/path to this socket
	 *
	 * @param string $address
	 * @return static
	 */
	public function bind($address);

	/**
	 * binds a name/address/path to this socket
	 *
	 * @param string $address
	 * @return static
	 */
	public function connect($address);

	/**
	 * start listen for incoming connections
	 *
	 * @return static
	 */
	public function listen();

	/**
	 * @return integer
	 */
	public function getType();

	/**
	 * receive up to $length bytes from connect()ed / accept()ed socket
	 *
	 * @param integer $length
	 * @param integer $flags
	 * @return mixed
	 */
	public function recv($length, $flags);

	/**
	 * receive up to $length bytes from socket
	 *
	 * @param integer $length
	 * @param integer $flags
	 * @param string $remote
	 * @return mixed
	 */
	public function recvFrom($length, $flags, &$remote);

	/**
	 * send given $buffer to connect()ed / accept()ed socket
	 *
	 * @param string $buffer
	 * @param integer $flags
	 * @return mixed
	 */
	public function send($buffer, $flags);

	/**
	 * send given $buffer to socket
	 *
	 * @param string $buffer
	 * @param integer $flags
	 * @param string $remote
	 * @return mixed
	 */
	public function sendTo($buffer, $flags, $remote);

	/**
	 * @return static
	 */
	public function shutdown();

	/**
	 * close this connect
	 *
	 * @return static
	 */
	public function close();

	/**
	 * @return string
	 */
	public function localAddress();

	/**
	 * @return string
	 */
	public function remoteAddress();

	/**
	 * @param integer $timeout
	 * @return static
	 */
	public function setTimeout($timeout);

}