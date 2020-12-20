<?php

namespace Tests\Socket;

use Polaris\Socket\ServerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class FactoryTest
 *
 * @package Tests\Socket
 */
class ServerFactoryTest extends TestCase
{

	/**
	 * @throws \Throwable
	 */
	public function testCreate()
	{
		$server = ServerFactory::create("tcp://127.0.0.1:8888");
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

}