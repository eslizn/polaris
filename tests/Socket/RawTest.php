<?php

namespace Polaris\Tests\Socket;

use Polaris\Tests\TestCase;

/**
 *
 */
class RawTest extends TestCase
{

	/**
	 * @return void
	 */
	public function testBind()
	{
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		$this->assertNotFalse($socket, socket_strerror(socket_last_error($socket)));
		$this->assertNotFalse(socket_bind($socket, '127.0.0.1', 18080), socket_strerror(socket_last_error($socket)));
		$this->assertNotFalse(socket_getsockname($socket, $host, $port), socket_strerror(socket_last_error($socket)));
		$this->assertEquals($host, '127.0.0.1');
		$this->assertEquals($port, 18080);
	}

}