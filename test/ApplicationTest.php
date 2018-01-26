<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionProperty;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManager;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\Mvc\Application;
use Zend\Mvc\DispatchListener;
use Zend\Mvc\HttpMethodListener;
use Zend\Mvc\MiddlewareListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\RouteListener;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Router\RouteStackInterface;

/**
 * @covers \Zend\Mvc\Application
 */
class ApplicationTest extends TestCase
{
    use EventListenerIntrospectionTrait;
    use ContainerTrait;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var ObjectProphecy
     */
    private $container;

    /**
     * @var ObjectProphecy
     */
    private $router;

    /**
     * @var ObjectProphecy
     */
    private $emitter;

    /**
     * @var EventManager
     */
    private $events;

    /**
     * @var array
     */
    private $listeners = [];

    public function setUp()
    {
        $this->container = $this->mockContainerInterface();

        $this->router = $this->prophesize(RouteStackInterface::class);
        $this->emitter = $this->prophesize(EmitterInterface::class);
        $this->events = new EventManager(new SharedEventManager());

        $this->injectServiceInContainer($this->container, 'config', []);

        $route = $this->prophesize(RouteListener::class);
        $this->injectServiceInContainer($this->container, RouteListener::class, $route->reveal());
        $this->listeners[RouteListener::class] = $route;

        $dispatch = $this->prophesize(DispatchListener::class);
        $this->injectServiceInContainer($this->container, DispatchListener::class, $dispatch->reveal());
        $this->listeners[DispatchListener::class] = $dispatch;

        $middleware = $this->prophesize(MiddlewareListener::class);
        $this->injectServiceInContainer($this->container, MiddlewareListener::class, $middleware->reveal());
        $this->listeners[MiddlewareListener::class] = $middleware;

        $viewManager = $this->prophesize(ViewManager::class);
        $this->injectServiceInContainer($this->container, ViewManager::class, $viewManager->reveal());
        $this->listeners[ViewManager::class] = $viewManager;

        $httpMethod = $this->prophesize(HttpMethodListener::class);
        $this->injectServiceInContainer($this->container, HttpMethodListener::class, $httpMethod->reveal());
        $this->listeners[HttpMethodListener::class] = $httpMethod;

        $this->application = new Application(
            $this->container->reveal(),
            $this->router->reveal(),
            $this->events,
            $this->emitter->reveal()
        );
    }

    public function testEventManagerIsPopulated()
    {
        $appEvents = $this->application->getEventManager();
        $this->assertSame($this->events, $appEvents);
    }

    public function testEventManagerListensOnApplicationContext()
    {
        $events      = $this->application->getEventManager();
        $identifiers = $events->getIdentifiers();
        $expected    = [Application::class];
        $this->assertEquals($expected, array_values($identifiers));
    }

    public function testContainerIsPopulated()
    {
        $this->assertSame($this->container->reveal(), $this->application->getContainer());
    }

    public function testConfigIsAProxyToContainer()
    {
        $this->container->get('config')
            ->shouldBeCalled()
            ->willReturn(['container' => 'config']);
        $appConfig = $this->application->getConfig();
        $this->assertEquals(
            ['container' => 'config'],
            $appConfig
        );
    }

    public function testEventsAreEmptyAtFirst()
    {
        $events = $this->application->getEventManager();
        $registeredEvents = $this->getEventsFromEventManager($events);
        $this->assertEquals([], $registeredEvents);

        $sharedEvents = $events->getSharedManager();
        $this->assertInstanceOf(SharedEventManager::class, $sharedEvents);
        $this->assertAttributeEquals([], 'identifiers', $sharedEvents);
    }

    public function testBootstrapRegistersListeners()
    {
        foreach ($this->listeners as $listener) {
            $listener->attach($this->events)->shouldBeCalled();
        }

        $this->application->bootstrap();
    }

    public function testBootstrapAlwaysRegistersDefaultListeners()
    {
        $application = new Application(
            $this->container->reveal(),
            $this->router->reveal(),
            $this->events,
            $this->emitter->reveal(),
            ['Custom']
        );

        $r = new ReflectionProperty($this->application, 'defaultListeners');
        $r->setAccessible(true);
        $defaultListeners = $r->getValue($this->application);
        foreach ($defaultListeners as $defaultListenerName) {
            $custom = $this->prophesize(ListenerAggregateInterface::class);
            $custom->attach($this->events)->shouldBeCalled();
            $this->injectServiceInContainer($this->container, $defaultListenerName, $custom->reveal());
        }

        $custom = $this->prophesize(ListenerAggregateInterface::class);
        $custom->attach($this->events)->shouldBeCalled();
        $this->injectServiceInContainer($this->container, 'Custom', $custom->reveal());

        $application->bootstrap();
    }

    public function testBootstrapAttachesInstanceOfListenerAggregate()
    {
        $custom = $this->prophesize(ListenerAggregateInterface::class);
        $custom->attach($this->events)->shouldBeCalled();
        $application = new Application(
            $this->container->reveal(),
            $this->router->reveal(),
            $this->events,
            $this->emitter->reveal(),
            [$custom->reveal()]
        );

        $application->bootstrap();
    }

    public function testBootstrapRegistersConfiguredMvcEvent()
    {
        $this->assertNull($this->application->getMvcEvent());
        $this->application->bootstrap();
        $event = $this->application->getMvcEvent();
        $this->assertInstanceOf(MvcEvent::class, $event);

        $this->assertFalse($event->isError());
        $this->assertNull($event->getRequest());
        $this->assertNull($event->getResponse());
        $this->assertSame($this->router->reveal(), $event->getRouter());
        $this->assertSame($this->application, $event->getApplication());
        $this->assertSame($this->application, $event->getTarget());
    }

    public function testBootstrapTriggersBootstrapEvent()
    {
        $called = false;
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_BOOTSTRAP,
            function ($e) use (&$called) {
                $this->assertInstanceOf(MvcEvent::class, $e);
                $called = true;
            }
        );
        $this->application->bootstrap();
        $this->assertTrue($called);
    }

    public function testBootstrapTriggersOnlyOnce()
    {
        $called = 0;
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_BOOTSTRAP,
            function ($e) use (&$called) {
                $this->assertInstanceOf(MvcEvent::class, $e);
                $called++;
            }
        );
        $this->application->bootstrap();
        $this->application->bootstrap();
        $this->assertEquals(1, $called);
    }

    public function testHandleTriggersBootstrapImplicitly()
    {
        $called = false;
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_BOOTSTRAP,
            function ($e) use (&$called) {
                $called = true;
            }
        );
        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
        $this->assertTrue($called);
    }

    public function testRequestAndResponseAreNotAvailableDuringBootstrap()
    {
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_BOOTSTRAP,
            function (MvcEvent $e) {
                $this->assertNull($e->getRequest());
                $this->assertNull($e->getResponse());
            }
        );
        $this->application->bootstrap();
    }

    public function testRequestIsAvailableForRouteEvent()
    {
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_ROUTE,
            function (MvcEvent $e) {
                $this->assertNotNull($e->getRequest());
            }
        );
        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
    }

    public function testRequestIsAvailableForDispatchEvent()
    {
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_ROUTE,
            function (MvcEvent $e) {
                $this->assertNotNull($e->getRequest());
            }
        );
        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
    }

    public function testHandleTriggersEventsInOrder()
    {
        $this->application->bootstrap();

        $expected = [
            MvcEvent::EVENT_ROUTE,
            MvcEvent::EVENT_DISPATCH,
            MvcEvent::EVENT_RENDER,
            MvcEvent::EVENT_FINISH,
        ];
        $triggered = [];
        $this->application->getEventManager()->attach(
            '*',
            function (MvcEvent $e) use (&$triggered) {
                $triggered[] = $e->getName();
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
        $this->assertEquals($expected, $triggered);
    }

    /**
     * @group ZF2-171
     */
    public function testFinishShouldTriggerEvenIfRouteEventReturnsResponse()
    {
        $this->application->bootstrap();

        $expected = [
            MvcEvent::EVENT_ROUTE,
            MvcEvent::EVENT_FINISH,
        ];
        $triggered = [];
        $this->application->getEventManager()->attach(
            '*',
            function (MvcEvent $e) use (&$triggered) {
                $triggered[] = $e->getName();
            },
            100
        );
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_ROUTE,
            function (MvcEvent $e) {
                return new Response();
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
        $this->assertEquals($expected, $triggered);
    }

    /**
     * @group ZF2-171
     */
    public function testRenderAndFinishShouldTriggerRunEvenIfRouteEventSetsError()
    {
        $this->application->bootstrap();

        $expected = [
            MvcEvent::EVENT_ROUTE,
            MvcEvent::EVENT_RENDER,
            MvcEvent::EVENT_FINISH,
        ];
        $triggered = [];
        $this->application->getEventManager()->attach(
            '*',
            function (MvcEvent $e) use (&$triggered) {
                $triggered[] = $e->getName();
            },
            100
        );
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_ROUTE,
            function (MvcEvent $e) {
                $e->setError('Route error');
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
        $this->assertEquals($expected, $triggered);
    }

    /**
     * @group ZF2-171
     */
    public function testFinishShouldTriggerEvenIfDispatchEventReturnsResponse()
    {
        $this->application->bootstrap();

        $expected = [
            MvcEvent::EVENT_ROUTE,
            MvcEvent::EVENT_DISPATCH,
            MvcEvent::EVENT_FINISH,
        ];
        $triggered = [];
        $this->application->getEventManager()->attach(
            '*',
            function (MvcEvent $e) use (&$triggered) {
                $triggered[] = $e->getName();
            },
            100
        );
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH,
            function (MvcEvent $e) {
                return new Response();
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
        $this->assertEquals($expected, $triggered);
    }

    /**
     * @group ZF2-171
     */
    public function testRenderAndFinishShouldTriggerEvenIfDispatchEventSetsError()
    {
        $this->application->bootstrap();

        $expected = [
            MvcEvent::EVENT_ROUTE,
            MvcEvent::EVENT_DISPATCH,
            MvcEvent::EVENT_RENDER,
            MvcEvent::EVENT_FINISH,
        ];
        $triggered = [];
        $this->application->getEventManager()->attach(
            '*',
            function (MvcEvent $e) use (&$triggered) {
                $triggered[] = $e->getName();
            },
            100
        );
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH,
            function (MvcEvent $e) {
                $e->setError('Dispatch error');
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/', 'GET', 'php://memory');
        $this->application->handle($request);
        $this->assertEquals($expected, $triggered);
    }

    public function testApplicationShouldBeEventTargetAtFinishEvent()
    {
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_FINISH,
            function (MvcEvent $e) {
                $this->assertSame($this->application, $e->getTarget());
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/sample', 'GET', 'php://memory');
        $this->application->handle($request);
    }

    /**
     * @group 2981
     */
    public function testReturnsResponseFromListenerWhenRouteEventShortCircuits()
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_ROUTE,
            function ($e) use ($testResponse) {
                return $testResponse;
            }
        );
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_FINISH,
            function (MvcEvent $e) use ($testResponse) {
                $this->assertSame($testResponse, $e->getResponse());
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/sample', 'GET', 'php://memory');
        $response = $this->application->handle($request);
        $this->assertSame($testResponse, $response);
    }

    /**
     * @group 2981
     */
    public function testReturnsResponseFromListenerWhenDispatchEventShortCircuits()
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH,
            function ($e) use ($testResponse) {
                return $testResponse;
            }
        );
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_FINISH,
            function (MvcEvent $e) use ($testResponse) {
                $this->assertSame($testResponse, $e->getResponse());
            }
        );

        $request = new ServerRequest([], [], 'http://example.local/sample', 'GET', 'php://memory');
        $response = $this->application->handle($request);
        $this->assertSame($testResponse, $response);
    }

    public function testEmitterGetterReturnsInjectedEmitter()
    {
        $emitter = $this->application->getEmitter();
        $this->assertSame($this->emitter->reveal(), $emitter);
    }

    public function testRunInvokesEmitterForResponse()
    {
        $response = new Response();
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_DISPATCH,
            function () use ($response) {
                return $response;
            }
        );
        $this->emitter->emit($response)->shouldBeCalled();
        $request = new ServerRequest([], [], 'http://example.local/sample', 'GET', 'php://memory');
        $this->application->run($request);
    }

    public function eventPropagation()
    {
        return [
            'route'    => [[MvcEvent::EVENT_ROUTE]],
            'dispatch' => [[MvcEvent::EVENT_DISPATCH, MvcEvent::EVENT_RENDER, MvcEvent::EVENT_FINISH]],
        ];
    }

    /**
     * @dataProvider eventPropagation
     */
    public function testEventPropagationStatusIsClearedBetweenEventsDuringRun($events)
    {
        $this->application->bootstrap();
        $event = $this->application->getMvcEvent();
        $event->stopPropagation(true);

        // Setup listeners that stop propagation, but do nothing else
        $marker = [];
        foreach ($events as $event) {
            $marker[$event] = true;
        }
        $listener = function (MvcEvent $e) use (&$marker) {
            $marker[$e->getName()] = $e->propagationIsStopped();
            $e->stopPropagation(true);
        };
        foreach ($events as $event) {
            $this->application->getEventManager()->attach($event, $listener);
        }

        $request = new ServerRequest([], [], 'http://example.local/sample', 'GET', 'php://memory');
        $this->application->handle($request);

        foreach ($events as $event) {
            $this->assertFalse($marker[$event], sprintf('Assertion failed for event "%s"', $event));
        }
    }

    public function testBadRequestShouldEmitAppropriateResponse()
    {
        $this->markTestIncomplete('Not implemented');
    }
}
