<?php

namespace Tests\Socket;

use Polaris\Socket\Factory;
use PHPUnit\Framework\TestCase;

/**
 * Class FactoryTest
 *
 * @package Tests\Socket
 */
class FactoryTest extends TestCase
{

	/**
	 * @throws \Throwable
	 */
	public function testCreateServer()
	{
		$server = Factory::createServer("tcp://127.0.0.1:8888");
		for (;;) {
			$conn = $server->accept();
			if (!$conn) {
				sleep(1);
				continue;
			}
			var_dump($conn->remoteAddress());
			$conn->close();
			break;
		}
	}

	/**
	 * @throws \Throwable
	 */
	public function testCreateClient()
	{
		$client = Factory::createClient("tcp://www.qq.com:80");
		var_dump($client->remoteAddress());
		var_dump($client->localAddress());
		$client->close();
	}

}