<?php
namespace Polaris\Jobs;

use Polaris\Jobs\Exceptions\DispatcherException;
use Polaris\Jobs\Interfaces\JobInterface;

/**
 * Trait DispatcherTrait
 * @package Polaris\Jobs
 */
trait DispatcherTrait
{

	/**
	 * @param mixed $data
	 * @param mixed $worker_id
	 * @param mixed $finish
	 * @return bool
	 */
	public function task($data, $worker_id = null, $finish = null)
	{
		if (!($this instanceof \Swoole\Server)) {
			throw new DispatcherException('the dispatcher only support swoole server!', -__LINE__);
		}
		if ($this->taskworker) {//support task in taskworker
			$worker_pid = $this->worker_pid;
			\Swoole\Coroutine::create(function () use ($data, $worker_id, $worker_pid) {
				$this->onTask($this, $worker_id, $worker_pid, $data);
			});
			return true;
		} else {
			return parent::task($data, $worker_id, $finish);
		}
	}

	/**
	 * @param mixed ...$arguments
	 */
	public function onTask(...$arguments)
	{
		if (\Swoole\Coroutine::getuid() === -1) { //support task coroutine with task_enable_coroutine=false
			\Swoole\Coroutine::create(function () use ($arguments) {
				try {
					return $this->onTask(...$arguments);
				} catch (\Throwable $e) {
					if (method_exists($arguments[0], 'handleException')) {
						$arguments[0]->handleException($e);
					}
				}
			});
		}
		switch (sizeof($arguments)) {
			case 2://task_enable_coroutine=true
				list($server, $task) = $arguments;
				$task_id = $task->id;
				$worker_id = $task->workerId;
				$data = $task->data;
				break;
			case 4:
				list($server, $task_id, $worker_id, $data) = $arguments;
			break;
			default:
				throw new \InvalidArgumentException('invalid argument!', -__LINE__);
		}
		list($class, $playload) = is_array($data) ? $data : [$data];
		$playload = $playload ? (is_array($playload) ? $playload : [$playload]) : [];
		$class = new $class(...$playload);
		if (!($class instanceof JobInterface)) {
			throw new DispatcherException('dispatch object must is JobInterface!', -__LINE__);
		}
		return $class->handle($server, $task_id, $worker_id);
	}

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	public function onFinish($data)
	{

	}

}