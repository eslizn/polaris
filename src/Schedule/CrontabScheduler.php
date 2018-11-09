<?php
namespace Polaris\Schedule;
use Polaris\Schedule\Exceptions\ScheduleException;

/**
 * Class CrontabScheduler
 * @package Polaris\Schedule
 */
class CrontabScheduler
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
	 * @param \Swoole\Server $srv
	 * @param integer $id
	 * @param  $pid
	 */
	public function __invoke($srv, $id, $pid)
	{
		\Swoole\Timer::tick(1000, function () use ($srv, $id, $pid) {
			$now = time();
			foreach (file_exists($this->crontab) ? include $this->crontab : [] as $k => $v) {
				if ($k % $srv->setting['task_worker_num'] != ($id - $srv->setting['worker_num'])) {
					continue;
				}
				if (!is_array($v) || !isset($v[0], $v[1])) {
					continue;
				}
				if (!$this->should($v[0], $now)) {
					continue;
				}
				$srv->task($v[1]);
			}
		});
	}

	/**
	 * @param string $rule
	 * @param integer $time
	 * @return bool
	 */
	protected function should($rule, $time = null)
	{
		$rules = array_filter(explode(' ', $rule));
		if (sizeof($rules) < 6) {
			array_unshift($rules, 0);
		}
		if (sizeof($rules) < 6) {
			throw new ScheduleException('crontab syntax invalid!', -__LINE__);
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
			if (strcmp($v[0], '*')) {
				$v[0] = array_map('trim', explode(',', $v[0]));
				if (!in_array($times[$k], $v[0])) {
					return false;
				}
			} else {
				if (!isset($v[1])) {
					continue;
				}
				if ($times[$k]%$v[1]) {
					return false;
				}
			}
		}
		return true;
	}

}