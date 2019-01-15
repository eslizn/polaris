<?php
namespace Polaris\Http\Middlewares;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class Dispatcher
 * @package Polaris\Http\Middlewares
 */
class Dispatcher implements RequestHandlerInterface
{

	use MiddlewareTrait;

}