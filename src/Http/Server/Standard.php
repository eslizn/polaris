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
 * Class Standard
 *
 * @package Polaris\Http\Server
 */
class Standard implements Server
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
	 * Handle constructor.
	 * @param string $workspace
	 * @param array $options
	 * @throws ErrorException
	 */
	public function __construct($workspace, $options = [])
	{
		$this->options = array_merge([
			'name' => basename($workspace),
			'workspace' => $workspace,
			'log_file' => sprintf('%s/log/%s.log', $workspace, basename($workspace)),
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

		//load middlewares
		$middlewares = [];
		if (file_exists($this->options['middlewares'])) {
			array_push($middlewares, ...(include $this->options['middlewares'] ?: []));
		}

		//append router
		array_push($middlewares, new RouterMiddleware($this->options['routes'], $this->options['namespace']));
		$this->dispatcher = new Middleware(new Response(), ...$middlewares);
	}

	/**
	 *
	 * @return void
	 */
	public function start()
	{
		try {
			ob_start();
			$request = Request::createFromGlobals($_SERVER)
				->withAttribute(static::class, $this);
			$response = $this->dispatcher->handle($request);
			header(sprintf('X-PHP-Response-Code: %d', $response->getStatusCode()), true, $response->getStatusCode());
			foreach ($response->getHeaders() as $name => $headers) {
				if (strcasecmp($name, 'Set-Cookie')) {
					header(sprintf('%s: %s', $name, implode(', ', $headers)));
				} else {
					$cookie = Cookies::parse(implode('; ', $headers));
					foreach ($cookie->cookies ?: [] as $key => $value) {
						setcookie($key, $value, $cookie->expires ?: null, $cookie->path ?: '/', $cookie->domain ?: '', $cookie->secure ?: false, $cookie->httponly ?: false);
					}
				}
			}
			echo $response->getBody()->getContents();
		} catch (Throwable $e) {
			$this->handleException($e);
		} finally {
			ob_end_flush();
		}
	}

	/**
	 * @param Throwable $e
	 * @return void
	 */
	public function handleException(Throwable $e)
	{
		echo '<pre>', $e, '</pre>';
	}

}