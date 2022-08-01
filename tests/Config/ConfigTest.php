<?php

namespace Polaris\Tests\Config;

use Polaris\Config\Config;
use Polaris\Config\ConfigInterface;
use Polaris\Tests\TestCase;

/**
 *
 */
class ConfigTest extends TestCase
{

    /**
     * @var ConfigInterface
     */
    protected ConfigInterface $config;

    /**
     * @param string|null $name
     * @param array $data
     * @param $dataName
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->config = new Config(__DIR__ . DIRECTORY_SEPARATOR . 'cases');
    }

    /**
     * @return void
     */
    public function testGet()
    {
        $this->assertIsInt($this->config->get('auth.get.now'));
        $this->assertIsInt($this->config['auth.get.now']);
        $this->assertEquals($this->config->get('auth.get.now'), $this->config['auth.get.now']);
    }

    /**
     * @return void
     */
    public function testSet()
    {
        $this->assertEquals(null, $this->config->get('auth.set'));
        $this->config->set('auth.set', time());
        $this->assertIsInt($this->config->get('auth.set'));
        $this->config->set('auth.set', strval(time()));
        $this->assertIsString($this->config->get('auth.set'));
    }

    /**
     * @return void
     */
    public function testHas()
    {
        $this->assertFalse($this->config->has('auth.has'));
        $this->config->set('auth.has', time());
        $this->assertTrue($this->config->has('auth.has'));
    }

    /**
     * @return void
     */
    public function testDel()
    {
        $this->assertTrue($this->config->has('auth.del'));
        $this->config->del('auth.del');
        $this->assertFalse($this->config->has('auth.del'));
    }

}