<?php
namespace Polaris\Dispatch;

use Polaris\Dispatch\Exceptions\DispatcherException;

/**
 * Class CrontabDispatcher
 * @package Polaris\Dispatch
 */
class CrontabDispatcher
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
				if (!static::should($v[0], $now)) {
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
	protected static function should($rule, $time = null)
	{
		$rules = array_filter(explode(' ', $rule));
		if (sizeof($rules) < 6) {
			array_unshift($rules, 0);
		}
		if (sizeof($rules) < 6) {
			throw new DispatcherException('crontab syntax invalid!', -__LINE__);
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