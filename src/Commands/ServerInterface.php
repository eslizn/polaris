<?php
namespace Polaris\Commands;

/**
 * Interface ServerInterface
 * @package Polaris\Commands
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