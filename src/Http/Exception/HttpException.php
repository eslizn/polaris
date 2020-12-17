<?php
namespace Polaris\Http\Exception;

use Polaris\Exception;
use Polaris\Http\Response;

/**
 * Class HttpException
 *
 * @package Polaris\Http\Exceptions
 */
class HttpException extends Exception
{

	/**
	 * HttpException constructor.
	 * @param int $statusCode
	 * @param string $statusText
	 */
	public function __construct($statusCode = 200, $statusText = '')
	{
		$statusText = $statusText ?: Response::getStatusText($statusCode);
		parent::__construct($statusText, $statusCode);
	}

	/**
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->getCode();
	}

	/**
	 * @return string
	 */
	public function getStatusText()
	{
		return $this->getMessage();
	}

}