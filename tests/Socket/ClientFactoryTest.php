<?php

namespace Tests\Socket;

use Polaris\Socket\ClientFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class ClientFactoryTest
 * @package Tests\Socket
 */
class ClientFactoryTest extends TestCase
{

	/**
	 * @throws \Throwable
	 */
	public function testCreate()
	{
		$client = ClientFactory::create("tcp://www.qq.com:80");
		var_dump($client->remoteAddress());
		var_dump($client->localAddress());
		$client->close();
	}

}