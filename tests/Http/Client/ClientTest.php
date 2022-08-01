<?php

namespace Polaris\Tests\Http\Client;

use Polaris\Http\Body;
use Polaris\Http\Client\Client;
use Polaris\Http\Client\Exception;
use Polaris\Http\Factory\UriFactory;
use Polaris\Http\Headers;
use Polaris\Http\Request;
use Polaris\Http\UploadedFile;
use Polaris\Tests\TestCase;

/**
 *
 */
class ClientTest extends TestCase
{

    /**
     * @return void
     * @throws \Polaris\Http\Client\Exception
     */
    public function testHandle()
    {
        $request = new Request('GET', UriFactory::createFromString('https://www.qq.com/'));
        $response = (new Client())->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getHeaders());
        $this->assertNotEmpty($response->getBody()->getContents());
    }

    /**
     * @return void
     */
    public function testSwooleHandle()
    {
        if (class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() < 1) {
            \Co\run(function() {
                $this->testHandle();
            });
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testAsyncHandle()
    {
        $request = new Request('GET', UriFactory::createFromString('https://www.qq.com/'));
        $promise = (new Client())->sendAsyncRequest($request);
        $response = $promise->wait();
        $this->assertEquals($response->getStatusCode(), 200);
    }

    /**
     * @return void
     * @throws \Polaris\Http\Client\Exception
     * @throws \Polaris\Http\Exception
     */
    public function testFormPostArray()
    {
        $request = new Request(
            'POST',
            UriFactory::createFromString('http://localhost:8080/'),
            new Headers([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]),
            null,
            [],
            new Body(http_build_query([
                'a' => [1, 2, 3],
            ]))
        );
        $response = (new Client())->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     *
     */
    public function testSwooleFormPostArray()
    {
        if (class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() < 1) {
            \Co\run(function() {
                $this->testFormPostArray();
            });
        }
    }

    /**
     * @return void
     * @throws Exception
     * @throws \Polaris\Http\Exception
     */
    public function testFormUploadArray()
    {
        $files = [];
        $tmp = [];
        for ($i = 0; $i < 3; $i++) {
            $tmp[$i] = tmpfile();
            $this->assertNotFalse($tmp[$i]);
            $this->assertNotFalse(fwrite($tmp[$i], strval(time())));
            rewind($tmp[$i]);
            $meta = stream_get_meta_data($tmp[$i]);
            $this->assertNotFalse($meta);
            $files[] = new UploadedFile($meta['uri'], sprintf('files[%d]', $i), null, filesize($meta['uri']));
        }
        $request = new Request(
            'POST',
            UriFactory::createFromString('http://localhost:8080/'),
            null,
            null,
            [],
            new Body(http_build_query([
                'a' => 1, //[1, 2, 3], @todo curl & swoole unsupported
            ])),
            $files
        );
        $response = (new Client())->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     *
     */
    public function testSwooleFormUploadArray()
    {
        if (class_exists('\Swoole\Coroutine') && \Swoole\Coroutine::getuid() < 1) {
            \Co\run(function() {
                $this->testFormUploadArray();
            });
        }
    }

}