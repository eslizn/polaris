<?php
namespace Polaris\Http\Response;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\ViewServiceProvider;
use Polaris\Http\HeadersInterface;
use Polaris\Http\Response;

/**
 * Class HtmlResponse
 * @package Polaris\Http\Response
 */
class HtmlResponse extends Response
{

    /**
     * HtmlResponse constructor.
     * @param $view
     * @param array $data
     * @param int $status
     * @param null $headers
     */
    public function __construct($view, $data = [], $status = 200, $headers = null)
    {
        $container = new Container();
        $container->bindIf('files', function () {
            return new Filesystem();
        }, true);
        $container->bindIf('events', function () {
            return new Dispatcher();
        }, true);
        $container->bindIf('config', function () {
            return [
                'view.paths' => [dirname(__DIR__, 6) . '/resources/views/'],
                'view.compiled' => dirname(__DIR__, 6) . '/resources/cache/views/',
            ];
        }, true);
        (new ViewServiceProvider($container))->register();
        $resolver = $container->make('view.engine.resolver');
        parent::__construct($status, $headers, $container['view']->make($view, $data)->render());
    }
    
}