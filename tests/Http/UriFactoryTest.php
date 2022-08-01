<?php

namespace Polaris\Tests\Http;

use Polaris\Http\Factory\UriFactory;
use Polaris\Http\Uri;
use Polaris\Tests\TestCase;

/**
 *
 */
class UriFactoryTest extends TestCase
{

    /**
     * @return void
     */
    public function testWithoutHostAndScheme()
    {
        $str = '/path/index?a=1&b=2';
        $uri = UriFactory::createFromString($str);
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertEquals($str, strval($uri));
    }

}