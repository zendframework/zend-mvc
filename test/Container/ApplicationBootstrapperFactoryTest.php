<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Bootstrapper\Aggregate;
use Zend\Mvc\Bootstrapper\BootstrapEmitter;
use Zend\Mvc\Bootstrapper\BootstrapperInterface;
use Zend\Mvc\Bootstrapper\DefaultListenerProvider;
use Zend\Mvc\Bootstrapper\ListenerProvider;
use Zend\Mvc\Container\ApplicationBootstrapperFactory;
use ZendTest\Mvc\ContainerTrait;

use function array_shift;

/**
 * @covers \Zend\Mvc\Container\ApplicationBootstrapperFactory
 */
class ApplicationBootstrapperFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testCanCreateConfiguredBootstrapper()
    {
        $factory   = new ApplicationBootstrapperFactory();
        $container = $this->mockContainerInterface();

        $listenerProvider = $this->prophesize(BootstrapperInterface::class)
            ->reveal();
        $this->injectServiceInContainer($container, ListenerProvider::class, $listenerProvider);

        /** @var Aggregate $bootstrapper */
        $bootstrapper = $factory->__invoke($container->reveal());
        $this->assertInstanceOf(Aggregate::class, $bootstrapper);

        $aggregated = $bootstrapper->getBootstrappers();
        $this->assertInstanceOf(
            DefaultListenerProvider::class,
            array_shift($aggregated)
        );
        $this->assertSame(
            $listenerProvider,
            array_shift($aggregated)
        );
        $this->assertInstanceOf(
            BootstrapEmitter::class,
            array_shift($aggregated)
        );
        $this->assertEmpty($aggregated);
    }
}
