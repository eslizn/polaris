<?php

namespace Polaris\Http\Server;

use ErrorException;
use Polaris\Http\Cookies;
use Polaris\Http\Middleware;
use Polaris\Http\Middleware\RouterMiddleware;
use Polaris\Http\Request;
use Polaris\Http\Response;
use Polaris\Http\Server;
use Throwable;

/**
 * Class Swoole
 *
 * @package Polaris\Http\Server
 */
class Swoole extends \Swoole\Http\Server implements Server
{

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @var Middleware
	 */
	protected $dispatcher;

	/**
	 * Server constructor.
	 * @param string $workspace
	 * @param array $options
	 * @throws ErrorException
	 */
	public function __construct($workspace, $options = [])
	{
		$this->options = array_merge([
			'name' => basename($workspace),
			'workspace' => $workspace,
			'http_gzip_level' => 0,
			'http_compression' => false,
			'pid_file' => sprintf('/var/run/%s_master.pid', basename($workspace)),
			'log_file' => sprintf('%s/log/%s.log', $workspace, basename($workspace)),
			'host' => '127.0.0.1',
			'port' => 0,
			'reload_async' => true,
			'worker_num' => $this->getQuantity(),
			'daemonize' => true,
			'routes' => sprintf('%s/etc/routes.php', $workspace),
			'middlewares' => sprintf('%s/etc/middlewares.php', $workspace),
			'namespace' => '\\App',
		], $options);
		set_exception_handler([$this, 'handleException']);
		set_error_handler(function ($code, $msg, $file, $line) {
			if ($code & E_ERROR) {
				throw new ErrorException($msg, $code, $code, $file, $line);
			}
		}, E_ALL | E_STRICT);
		parent::__construct($this->options['host'], $this->options['port']);
		parent::set($this->options);
		foreach (get_class_methods($this) as $method) {
			if (preg_match('/^on(\w+)$/i', $method, $matches)) {
				$this->on($matches[1], [$this, $method]);
			}
		}
	}

	/**
	 * 启动进程数
	 *
	 * @return int
	 */
	protected function getQuantity(){
		$total = 1;
		if (is_file('/proc/cpuinfo')) {
			preg_match_all('/^processor/m', file_get_contents('/proc/cpuinfo'), $matches);
			$total = count($matches[0]);
		} else if ('WIN' == strtoupper(substr(PHP_OS, 0, 3))) {
			$process = @popen('wmic cpu get NumberOfCores', 'rb');
			if (false !== $process) {
				fgets($process);
				$total = intval(fgets($process));
				pclose($process);
			}
		} else {
			$process = @popen('sysctl -a', 'rb');
			if (false !== $process) {
				$output = stream_get_contents($process);
				preg_match('/hw.ncpu: (\d+)/', $output, $matches);
				if ($matches) {
					$total = intval($matches[1][0]);
				}
				pclose($process);
			}
		}
		return $total > 1 ? floor($total/2) : $total;
	}

	/**
	 * @return bool
	 */
	public function start()
	{
		//load middlewares
		$middlewares = [];
		if (file_exists($this->options['middlewares'])) {
			array_push($middlewares, ...(include $this->options['middlewares']) ?: []);
		}
		//append router
		array_push($middlewares, new RouterMiddleware($this->options['routes'], $this->options['namespace']));
		$this->dispatcher = new Middleware(new Response(), ...$middlewares);
		return parent::start();
	}

	/**
	 * http请求处理
	 *
	 * @param \Swoole\Http\Request $reader
	 * @param \Swoole\Http\Response $writer
	 */
	public function onRequest(\Swoole\Http\Request $reader, \Swoole\Http\Response $writer)
	{
		try {
			$request = Request::createFromSwoole($reader)
				->withAttribute(\Swoole\Http\Response::class, $writer)
				->withAttribute(static::class, $this);
			$response = $this->dispatcher->handle($request);
			//response to writer
			$writer->status($response->getStatusCode());
			foreach ($response->getHeaders() as $name => $headers) {
				if (strcasecmp($name, 'Set-Cookie')) {
					$writer->header($name, implode(', ', $headers));
				} else {
					$cookie = Cookies::parse(implode('; ', $headers));
					foreach ($cookie->cookies ?: [] as $key => $value) {
						$writer->cookie($key, $value, $cookie->expires ?: null, $cookie->path ?: '/', $cookie->domain ?: '', $cookie->secure ?: false, $cookie->httponly ?: false);
					}
				}
			}
			if (!$response->getHeader('Server')) {
				$writer->header('Server', 'Polaris');
			}
			if ($response->getBody()->getSize()) {
				$writer->write($response->getBody()->getContents());
			}
		} catch (Throwable $e) {
			$this->handleException($e);
		} finally {
			$writer->end();
		}
	}

	/**
	 * @param Throwable $e
	 * @return void
	 */
	public function handleException(Throwable $e)
	{
		printf("[%s # %d][%s:%d]%s\n",
			date('Y-m-d H:i:s'),
			getmypid(),
			basename($e->getFile()),
			$e->getLine(),
			strval($e)
		);
	}

}
