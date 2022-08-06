<?php

namespace Polaris\Events;

use Psr\Container\ContainerInterface;

/**
 * AbstractEvent
 *
 * @package Polaris\Events
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

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

}
