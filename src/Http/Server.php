<?php
namespace Polaris\Http;

use Polaris\Http\Middlewares\MiddlewareTrait;
use Polaris\Http\Middlewares\RouterMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Polaris\Http\Cookies;

/**
 * Class Server
 * @package Polaris\Http
 */
class Server extends \Swoole\Http\Server implements RequestHandlerInterface
{

	use MiddlewareTrait;

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * Server constructor.
	 * @param string $workspace
	 * @param array $options
	 */
	public function __construct($workspace, $options = [])
	{
		if (!defined('ENVIRONMENT')) {
			define('ENVIRONMENT', 'production');
		}
		if (!defined('DEBUG')) {
			define('DEBUG', strcasecmp(ENVIRONMENT, 'development') ? false : true);
		}
		$this->options = array_merge([
			'name' => basename($workspace),
			'workspace' => $workspace,
			'host' => '127.0.0.1',
			'port' => 0,
			'reload_async' => true,
			'worker_num' => $this->getQuantity(),
			'daemonize' => true,
			'routes' => sprintf('%s/etc/routes.php', $workspace),
			'middlewares' => sprintf('%s/etc/middlewares.php', $workspace),
			'namespace' => '\\App',
		], $options);
		set_exception_handler(array($this, 'handleException'));
		set_error_handler(function ($code, $msg, $file, $line) {
			if ($code & E_ERROR) {
				throw new \ErrorException($msg, $code, $code, $file, $line);
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
	 * @return bool|void
	 */
	public function start()
	{
		//load middlewares
		if (file_exists($this->options['middlewares'])) {
			$this->middlewares(include $this->options['middlewares'] ?: []);
		}
		//append router
		$this->middlewares(new RouterMiddleware($this->options['routes'], $this->options['namespace']));
		parent::start();
	}

	/**
     * @return mixed|void
     */
	public function restart()
    {
        $this->stop();
        sleep(1);
        $this->start();
    }

	/**
	 * @param mixed $offset
	 * @param mixed $default
	 * @return mixed
	 */
    public function getOptions($offset = null, $default = null)
	{
		return is_null($offset) ? $this->options : (isset($this->options[$offset]) ? $this->options[$offset] : $default);
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
		return $total > 1 ? intval($total/2) : $total;
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
			$response = $this->handle($request);
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
			if ($response->getBody()->getSize()) {
				if ($response instanceof Response\FileResponse) {
					$file = $response->getBody()->getContents();
					$writer->sendfile($file);//sendfile will end
				} else {
					$writer->write($response->getBody()->getContents());
					$writer->end();
				}
			} else {
				$writer->end();
			}
		} catch (\Throwable $e) {
			$this->handleException($e);
			$writer->end();
        }
	}

	/**
	 * @param \Throwable $e
	 * @return ResponseInterface
	 */
	public function handleException(\Throwable $e)
	{
		printf("[%s # %d][%s:%d]%s\n",
			date('Y-m-d H:i:s'),
			getmypid(),
			basename($e->getFile()),
			$e->getLine(),
			DEBUG ? $e->__toString() : $e->getMessage()
		);
	}

}
