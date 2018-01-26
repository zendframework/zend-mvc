<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\Application;
use Zend\Mvc\Container\ApplicationFactory;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\DispatchListener;
use Zend\Mvc\Emitter\EmitterStack;
use Zend\Mvc\HttpMethodListener;
use Zend\Mvc\MiddlewareListener;
use Zend\Mvc\RouteListener;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Router\RouteStackInterface;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ApplicationFactory
 */
class ApplicationFactoryTest extends TestCase
{
    use ContainerTrait;

    /**
     * @var ObjectProphecy|ContainerInterface
     */
    private $container;

    /**
     * @var ApplicationFactory
     */
    private $factory;

    /**
     * @var ObjectProphecy|RouteStackInterface
     */
    private $router;

    /**
     * @var ObjectProphecy|EmitterInterface
     */
    private $emitter;

    /**
     * @var EventManager
     */
    private $events;

    public function setUp()
    {
        $this->container = $this->mockContainerInterface();
        $this->factory = new ApplicationFactory();

        $this->router = $this->prophesize(RouteStackInterface::class);
        $this->emitter = $this->prophesize(EmitterInterface::class);
        $this->events = $this->prophesize(EventManagerInterface::class);

        $this->injectServiceInContainer($this->container, 'Zend\Mvc\Router', $this->router);
        $this->injectServiceInContainer($this->container, 'EventManager', $this->events);
        $this->injectServiceInContainer($this->container, 'config', []);
    }

    public function testInjectsEmitterWhenAvailable()
    {
        $this->injectServiceInContainer(
            $this->container,
            EmitterInterface::class,
            $this->emitter->reveal()
        );
        $app = $this->factory->__invoke($this->container->reveal(), Application::class);
        $this->assertSame($this->emitter->reveal(), $app->getEmitter());
    }

    public function testInjectsNewEmitterStackWhenEmitterNotInContainer()
    {
        $app = $this->factory->__invoke($this->container->reveal(), Application::class);
        $emitter = $app->getEmitter();
        $this->assertInstanceOf(EmitterStack::class, $emitter);
        $this->assertCount(1, $emitter);
        $this->assertInstanceOf(SapiEmitter::class, $emitter[0]);
    }

    public function testInjectsListenersFromConfig()
    {
        // application default listeners
        $route = $this->prophesize(RouteListener::class);
        $this->injectServiceInContainer($this->container, RouteListener::class, $route->reveal());
        $dispatch = $this->prophesize(DispatchListener::class);
        $this->injectServiceInContainer($this->container, DispatchListener::class, $dispatch->reveal());
        $middleware = $this->prophesize(MiddlewareListener::class);
        $this->injectServiceInContainer($this->container, MiddlewareListener::class, $middleware->reveal());
        $viewManager = $this->prophesize(ViewManager::class);
        $this->injectServiceInContainer($this->container, ViewManager::class, $viewManager->reveal());
        $httpMethod = $this->prophesize(HttpMethodListener::class);
        $this->injectServiceInContainer($this->container, HttpMethodListener::class, $httpMethod->reveal());


        $listener = $this->prophesize(ListenerAggregateInterface::class);
        $listener->attach($this->events)->shouldBecalled();
        $this->injectServiceInContainer($this->container, 'listenerToInject', $listener->reveal());
        $this->injectServiceInContainer(
            $this->container,
            'config',
            [
                Application::class => [
                    'listeners' => ['listenerToInject']
                ]
            ]
        );

        $app = $this->factory->__invoke($this->container->reveal(), Application::class);
        $app->bootstrap();
    }
}
