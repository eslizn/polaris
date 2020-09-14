<?php
namespace Polaris\Http\Response;

use Polaris\Http\Response;

/**
 * Class RedirectResponse
 * @package Polaris\Http\Response
 */
class RedirectResponse extends Response
{

	/**
	 * RedirectResponse constructor.
	 * @param string $url
	 * @param bool $temporary
	 */
	public function __construct($url, $temporary = true)
	{
		parent::__construct($temporary ? 302 : 301);
		$this->headers->set('Location', $url);
	}

}