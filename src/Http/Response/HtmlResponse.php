<?php
namespace Polaris\Http\Response;

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
    	$render = \Closure::bind(function ($view, $data) {
			ob_start();
			unset($data['this']);
			extract($data);
			include $view;
			return ob_get_clean();
		}, isset($data['this']) ? $data['this'] : $this);
        parent::__construct($status, $headers, $render($view, $data));
    }
    
}