<?php

namespace Polaris\Socket;

/**
 * Interface Reader
 * @package Polaris\Socket
 */
interface Reader
{

	/**
	 * @param integer $length
	 * @return string
	 */
	public function read($length);

}
