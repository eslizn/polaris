<?php
namespace Polaris\Schedule;

use Polaris\Schedule\Exceptions\ScheduleException;

/**
 * Trait SchedulerTrait
 * @package Polaris\Schedule
 */
trait SchedulerTrait
{

	/**
	 * @param mixed $data
	 * @param mixed $worker_id
	 * @param mixed $finish_callback
	 * @return bool
	 */
	public function task($data, $worker_id = null, $finish_callback = null)
	{
		if (!($this instanceof \Swoole\Server)) {
			throw new ScheduleException('the scheduler only support swoole server!', -__LINE__);
		}
		if ($this->taskworker) {
			$this->onTask($this, $this->worker_id, $this->worker_pid, $data);
		} else {
			parent::task($data, $worker_id, $finish_callback);
		}
		return true;
	}

	/**
	 * @param \Swoole\Server $srv
	 * @param integer $id
	 * @param integer $pid
	 * @param mixed $data
	 * @return mixed
	 */
	public function onTask(\Swoole\Server $srv, $id, $pid, $data)
	{
		list($callable, $playload) = is_array($data) ? $data : [$data];
		$playload = $playload ? (is_array($playload) ? $playload : [$playload]) : [];
		if (is_string($callable) && class_exists($callable) && method_exists($callable, '__invoke')) {
			$callable = function ($srv, $id, $pid) use ($callable, $playload) {
				try {
					$object = new $callable(...$playload);
					return $object->__invoke($srv, $id, $pid);
				} catch (\Throwable $e) {
					if (!method_exists($object, 'handleException')) {
						throw $e;
					}
					$object->handleException($e);
				}
			};
		}
		if (!is_callable($callable)) {
			throw new ScheduleException('schedule object must is callable!', -__LINE__);
		}
		\Swoole\Coroutine::create(function () use ($srv, $id, $pid, $callable) {
			try {
				return $callable($srv, $id, $pid);
			} catch (\Throwable $e) {
				if (method_exists($srv, 'handleException')) {
					$srv->handleException($e);
				}
			}
		});
	}

	/**
	 * @param mixed $data
	 * @return mixed
	 */
	public function onFinish($data)
	{

	}

}