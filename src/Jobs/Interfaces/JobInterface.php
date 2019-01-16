<?php
namespace Polaris\Jobs\Interfaces;

/**
 * Interface JobInterface
 * @package Polaris\Jobs\Interfaces
 */
interface JobInterface
{

	/**
	 * @param \Swoole\Server $server
	 * @param integer $task_id
	 * @param integer $worker_id
	 * @return mixed
	 */
	public function handle(\Swoole\Server $server, $task_id, $worker_id);

}