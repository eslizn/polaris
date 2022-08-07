<?php

namespace Polaris\Tests\Socket;

use Polaris\Socket\Exception;
use Polaris\Socket\Persist;
use Polaris\Tests\TestCase;

/**
 *
 */
class PersistTest extends TestCase
{

	/**
	 * @throws Exception
	 */
	public function testCreateClient()
	{
		$socket = Persist::createSocket('tcp')
			->connect('www.qq.com', 80);
		$this->assertEquals($socket->send("GET / HTTP/1.1\r\nHost: www.qq.com\r\n\r\n"), 36);
		$this->assertNotEmpty($socket->read(1024));
		$socket->close();
	}

	/**
	 * @throws Exception
	 */
	public function testCoroutineCreateClient()
	{
		if (class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() < 1) {
			\Co\run(function() {
				$this->testCreateClient();
			});
		}
	}

	/**
	 *
	 */
	public function testTimeout()
	{
		try {
			$socket = Persist::createSocket('tcp')
				->setTimeout(1)
				->connect('www.qq.com', 80);
			$this->assertEquals($socket->send("GET / HTTP/1.1\r\nHost: www.qq.com\r\n\r\n"), 36);
			$this->assertNotEmpty($socket->read(1024));
			$socket->read(1024);
			$socket->close();
		} catch (\Polaris\Exception $e) {
			$this->assertInstanceOf(Exception\TimeoutException::class, $e);
		}
	}

	/**
	 *
	 */
	public function testCoroutineTimeout()
	{
		if (class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() < 1) {
			\Co\run(function() {
				$this->testTimeout();
			});
		}
	}

	/**
	 * @return void
	 */
	public function testCoroutineReuseClient()
	{
		if (class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() < 1) {
			for ($i = 0; $i < 10; $i++) {
				\Co\run(function() {
					$this->testCreateClient();
				});
			}
		}
	}

}