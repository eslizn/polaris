<?php

namespace Polaris\Db\Exception;

use Polaris\Db\Db;

/**
 * Class Exception
 * @package Polaris\Db\Exception
 */
class Exception extends \Polaris\Exception
{

	/**
	 * @var Db
	 */
	protected $db;

	/**
	 * Exception constructor.
	 * @param string $message
	 * @param int $code
	 * @param Db|null $db
	 */
	public function __construct($message = "", $code = 0, Db $db = null)
	{
		parent::__construct($message, $code, null);
	}

}
