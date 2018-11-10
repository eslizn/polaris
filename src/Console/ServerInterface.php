<?php
namespace Polaris\Console;

/**
 * Interface ServerInterface
 * @package Polaris\Console
 */
interface ServerInterface
{

    /**
     * @return mixed
     */
    public function start();

    /**
     * @return mixed
     */
    public function stop();

    /**
     * @return mixed
     */
    public function restart();

    /**
     * @return mixed
     */
    public function reload();

}