<?php

namespace Polaris\Tests\Pool;

/**
 *
 */
class Connection
{

    /**
     *
     */
    public function __destruct()
    {
        echo __METHOD__;
    }

}