<?php

namespace Polaris\Tests\Pool;

use Polaris\Pool\Pool;
use Polaris\Tests\TestCase;

/**
 *
 */
class PoolTest extends TestCase
{

    /**
     * @return void
     */
    public function testCloseDestruct()
    {
        if (!class_exists(\Swoole\Coroutine::class)) {
            return;
        }
        \Swoole\Coroutine::create(function () {
            $pool = new Pool(function () {
                return new Connection();
            });
            //var_dump($pool->pop());
            //$pool->close();
        });
        \Swoole\Event::wait();
    }

}