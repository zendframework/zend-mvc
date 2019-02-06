<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

/**
 * Helper methods for mocking Psr\Container\ContainerInterface.
 */
trait ContainerTrait
{
    /**
     * Returns a prophecy for ContainerInterface.
     *
     * By default returns false for unknown `has('service')` method.
     *
     * @return ContainerInterface|ObjectProphecy
     */
    protected function mockContainerInterface() : ObjectProphecy
    {
        /** @var TestCase $this */
        $container = $this->prophesize(ContainerInterface::class);
        $container->has(Argument::type('string'))->willReturn(false);

        return $container;
    }

    /**
     * Inject a service into the container mock.
     *
     * Adjust `has('service')` and `get('service')` returns.
     *
     * @param ContainerInterface|ObjectProphecy $container
     * @param mixed                             $service
     */
    protected function injectServiceInContainer(ObjectProphecy $container, string $serviceName, $service) : void
    {
        $container->has($serviceName)->willReturn(true);
        $container->get($serviceName)->willReturn($service);
    }
}
