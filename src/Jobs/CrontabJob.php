<?php
namespace Polaris\Jobs;

use Polaris\Jobs\Exceptions\JobException;
use Polaris\Jobs\Interfaces\JobInterface;

/**
 * Class CrontabJob
 * @package Polaris\Jobs
 */
class CrontabJob implements JobInterface
{

	/**
	 * @var string
	 */
	protected $crontab = '';

	/**
	 * CrontabScheduler constructor.
	 * @param string $crontab
	 */
	public function __construct($crontab)
	{
		$this->crontab = $crontab;
	}

	/**
	 * @param \Swoole\Server $server
	 * @param int $task_id
	 * @param int $worker_id
	 * @return mixed|void
	 */
	public function handle(\Swoole\Server $server, $task_id, $worker_id)
	{
		\Swoole\Timer::tick(1000, function () use ($server, $task_id, $worker_id) {
			$now = time();
			foreach (file_exists($this->crontab) ? include $this->crontab : [] as $k => $v) {
				if ($k % $server->setting['task_worker_num'] != ($task_id - $server->setting['worker_num'])) {
					continue;
				}
				if (!is_array($v) || !isset($v[0], $v[1])) {
					continue;
				}
				if (!static::should($v[0], $now)) {
					continue;
				}
				$server->task($v[1]);
			}
		});
	}

	/**
	 * @param string $rule
	 * @param integer $time
	 * @return bool
	 */
	protected static function should($rule, $time = null)
	{
		$rules = array_filter(explode(' ', $rule));
		if (sizeof($rules) < 6) {
			array_unshift($rules, 0);
		}
		if (sizeof($rules) < 6) {
			throw new JobException('crontab syntax invalid!', -__LINE__);
		}
		$times = array_map('intval', [
			date('s', $time),
			date('i', $time),
			date('H', $time),
			date('d', $time),
			date('m', $time),
			date('w', $time) + 1,
		]);
		foreach ($rules as $k => $v) {
			$v = array_map('trim', explode('/', $v));
			if (isset($v[1]) && $times[$k]%$v[1]) {
				return false;
			}
			if (!strcmp($v[0], '*')) {
				continue;
			}
			if (strpos($v[0], '-') !== false) {
				$range = array_map('trim', explode('-', $v[0]));
				if (isset($range[0], $range[1]) && ($times[$k] < $range[0] || $times[$k] > $range[1])) {
					return false;
				}
				continue;
			}
			$list = array_map('trim', explode(',', $v[0]));
			if (!in_array($times[$k], $list)) {
				return false;
			}
		}
		return true;
	}

}
