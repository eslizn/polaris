<?php
namespace Polaris\Http\Response;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\ViewServiceProvider;
use Polaris\Http\HeadersInterface;
use Polaris\Http\Request;
use Polaris\Http\Response;

/**
 * Class HtmlResponse
 * @package Polaris\Http\Response
 */
class HtmlResponse extends Response
{

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Container
	 */
	protected $container;

	/**
	 * @var EngineResolver
	 */
	protected $resolver;

    /**
     * HtmlResponse constructor.
	 *
     * @param string $view
     * @param array $data
     * @param int $status
     * @param null $headers
     */
    public function __construct($view, $data = [], $status = 200, $headers = null, Request $request = null)
    {
    	$this->request = $request;
    	if ($request) {
    		$data = array_merge(compact('request'), $data);
		}
    	$this->container = $request ? $request->getAttribute(Container::class) : null;
    	if (!$this->container) {
			$this->container = new Container();
			$this->container->bindIf('files', function () {
				return new Filesystem();
			}, true);
			$this->container->bindIf('events', function () {
				return new Dispatcher();
			}, true);
			$this->container->bindIf('config', function () {
				return [
					'view.paths' => [dirname(__DIR__, 6) . '/resources/views/'],
					'view.compiled' => dirname(__DIR__, 6) . '/cache/views/',
				];
			}, true);
		}
        (new ViewServiceProvider($this->container))->register();
        $this->resolver = $this->container->make('view.engine.resolver');
        parent::__construct($status, $headers, $this->container['view']->make($view, $data)->render());
    }

	/**
	 * @return BladeCompiler
	 */
    protected function getCompiler($engine = 'blade')
	{
		return $this->resolver->resolve($engine)->getCompiler();
	}

}