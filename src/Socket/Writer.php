<?php

namespace Polaris\Socket;

/**
 * Interface Writer
 * @package Polaris\Socket
 */
interface Writer
{

	/**
	 * @param string $buffer
	 * @return integer
	 */
	public function write($buffer);

}
