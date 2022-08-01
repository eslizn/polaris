<?php

namespace Polaris\Tests;

use Dotenv\Dotenv;

/**
 *
 */
class TestCase extends \PHPUnit\Framework\TestCase
{

    /**
     * @param string|null $name
     * @param array $data
     * @param $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        Dotenv::createUnsafeImmutable(dirname(__DIR__))->safeLoad();
        parent::__construct($name, $data, $dataName);
    }

}