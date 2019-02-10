<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Bootstrapper;

use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\NotFoundExceptionInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\Bootstrapper\DefaultListenerProvider;
use ZendTest\Mvc\ContainerTrait;

use function array_pop;

/**
 * @covers \Zend\Mvc\Bootstrapper\DefaultListenerProvider
 */
class DefaultListenerProviderTest extends TestCase
{
    use ContainerTrait;

    private $defaultListeners = [
        'RouteListener',
        'MiddlewareListener',
        'DispatchListener',
        'HttpMethodListener',
        'ViewManager',
        'SendResponseListener',
    ];

    public function testRegistersDefaultListenersOnBootstrap()
    {
        $events    = new EventManager();
        $container = $this->mockContainerInterface();
        foreach ($this->defaultListeners as $listenerKey) {
            $listener = $this->prophesize(ListenerAggregateInterface::class);
            $listener->attach($events)
                ->shouldBeCalled();
            $this->injectServiceInContainer($container, $listenerKey, $listener->reveal());
        }

        $application = $this->prophesize(ApplicationInterface::class);
        $application->getEventManager()
            ->willReturn($events);

        $bootstrapper = new DefaultListenerProvider($container->reveal());

        $bootstrapper->bootstrap($application->reveal());
    }

    public function testDoesNotPullListenersFromContainerOnInstantiation()
    {
        $container = $this->mockContainerInterface();
        $container->get(Argument::any())
            ->shouldNotBeCalled();

        new DefaultListenerProvider($container->reveal());
    }

    public function testFailsIfListenerIsMissingFromContainer()
    {
        $events    = new EventManager();
        $container = $this->mockContainerInterface();

        $defaultListeners = $this->defaultListeners;
        $missing          = array_pop($defaultListeners);
        foreach ($defaultListeners as $listenerKey) {
            $listener = $this->prophesize(ListenerAggregateInterface::class);
            $this->injectServiceInContainer($container, $listenerKey, $listener->reveal());
        }
        $exception = $this->prophesize(NotFoundExceptionInterface::class)
            ->willExtend(Exception::class)
            ->reveal();
        $container->get($missing)
            ->willThrow($exception);

        $application = $this->prophesize(ApplicationInterface::class);
        $application->getEventManager()
            ->willReturn($events);

        $bootstrapper = new DefaultListenerProvider($container->reveal());

        $this->expectExceptionObject($exception);
        $bootstrapper->bootstrap($application->reveal());
    }
}
