<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\EventManager\EventManager;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\Application;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\Bootstrapper\ListenerProvider;
use Zend\Mvc\Container\ApplicationListenerProviderFactory;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ApplicationListenerProviderFactory
 */
class ApplicationListenerProviderFactoryTest extends TestCase
{
    use ContainerTrait;

    /** @var ObjectProphecy */
    private $container;

    /** @var ApplicationListenerProviderFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory   = new ApplicationListenerProviderFactory();
        $this->container = $this->mockContainerInterface();
    }

    public function testCreatesConfiguredListenerProvider()
    {
        $events      = new EventManager();
        $application = $this->prophesize(ApplicationInterface::class);
        $application->getEventManager()
            ->willReturn($events);

        $listenerAggregate = $this->prophesize(ListenerAggregateInterface::class);
        $listenerAggregate->attach($events)
            ->shouldBeCalled();

        $config[Application::class]['listeners'] = [
            $listenerAggregate,
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $bootstrapper = $this->factory->__invoke($this->container->reveal());
        $bootstrapper->bootstrap($application->reveal());
    }

    public function testListenerProviderCanBeCreatedWithMissingListenersConfig()
    {
        $this->injectServiceInContainer($this->container, 'config', []);

        $bootstrapper = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(ListenerProvider::class, $bootstrapper);
    }

    public function testListenerProviderCanBeCreatedWithMissingConfigService()
    {
        $bootstrapper = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(ListenerProvider::class, $bootstrapper);
    }
}
