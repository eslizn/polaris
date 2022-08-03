<?php

namespace Polaris\Event;


use Psr\Container\ContainerInterface;

/**
 * AbstractEvent
 *
 * @package Polaris\Event
 * @author eslizn <eslizn@gmail.com>
 */
abstract class AbstractEvent
{

	/**
	 * @var ContainerInterface
	 */
	protected ContainerInterface $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

}
