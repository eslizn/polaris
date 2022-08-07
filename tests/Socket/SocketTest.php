<?php

namespace Polaris\Tests\Socket;

use Polaris\Socket\Exception;
use Polaris\Socket\Socket;
use Polaris\Tests\TestCase;

/**
 *
 */
class SocketTest extends TestCase
{

	/**
	 * @throws Exception
	 */
	public function testCreateClient()
	{
		$socket = Socket::createSocket('tcp')
			->connect('www.qq.com', 80);
		$this->assertEquals($socket->send("GET / HTTP/1.1\r\nHost: www.qq.com\r\n\r\n"), 36);
		$this->assertNotEmpty($socket->read(1024));
		$socket->close();
	}

	/**
	 *
	 */
	public function testCoroutineCreateClient()
	{
		if (!class_exists('\Swoole\Runtime')) {
			return;
		}
		\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
		\Co\run(function() {
			$this->testCreateClient();
		});
	}

	/**
	 *
	 */
	public function testTimeout()
	{
		try {
			$socket = Socket::createSocket('tcp')
				->setTimeout(1)
				->connect('www.qq.com', 80);
			$this->assertNotEmpty($socket->send("GET / HTTP/1.1\r\nHost: www.baidu.com\r\n\r\n"));
			$this->assertNotEmpty($socket->read(4096));
			$socket->read(4096);
			$socket->close();
		} catch (Exception $e) {
			$this->assertInstanceOf(Exception\TimeoutException::class, $e);
		}
	}

	/**
	 *
	 */
	public function testCoroutineTimeout()
	{
		if (!class_exists('\Swoole\Runtime')) {
			return;
		}
		\Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL);
		\Co\run(function() {
			$this->testTimeout();
		});
	}

}