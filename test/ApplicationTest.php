<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\ModuleManager\Listener\ConfigListener;
use Zend\ModuleManager\ModuleEvent;
use Zend\Mvc\Application;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Container\ServiceManagerConfig;
use Zend\Mvc\Container\ServiceListenerFactory;
use Zend\Router;
use Zend\Router\RouteResult;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Model\ViewModel;

class ApplicationTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var Application
     */
    protected $application;

    public function setUp()
    {
        $serviceListener = new ServiceListenerFactory();
        $r = new ReflectionProperty($serviceListener, 'defaultServiceConfig');
        $r->setAccessible(true);
        $serviceConfig = $r->getValue($serviceListener);

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            (new Router\ConfigProvider())->getDependencyConfig()
        );

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            [
                'invokables' => [
                    'ViewManager'          => TestAsset\MockViewManager::class,
                    'BootstrapListener'    => TestAsset\StubBootstrapListener::class,
                ],
                'factories' => [
                    'Router' => Router\RouterFactory::class,
                    EmitterInterface::class => function () {
                        $emitter = $this->prophesize(EmitterInterface::class);
                        return $emitter->reveal();
                    },
                ],
                'services' => [
                    'config' => [],
                    'ApplicationConfig' => [
                        'modules' => [
                            'Zend\Router',
                        ],
                        'module_listener_options' => [
                            'config_cache_enabled' => false,
                            'cache_dir'            => 'data/cache',
                            'module_paths'         => [],
                        ],
                    ],
                ],
            ]
        );
        $this->serviceManager = new ServiceManager();
        (new ServiceManagerConfig($serviceConfig))->configureServiceManager($this->serviceManager);
        $this->serviceManager->setAllowOverride(true);
        $this->application = $this->serviceManager->get('Application');
    }

    public function getConfigListener()
    {
        $manager   = $this->serviceManager->get('ModuleManager');
        $listeners = $this->getArrayOfListenersForEvent(ModuleEvent::EVENT_LOAD_MODULE, $manager->getEventManager());
        return array_reduce($listeners, function ($found, $listener) {
            if ($found || ! is_array($listener)) {
                return $found;
            }
            $listener = array_shift($listener);
            if ($listener instanceof ConfigListener) {
                return $listener;
            }
        });
    }


    public function testEventManagerIsPopulated()
    {
        $events       = $this->serviceManager->get('EventManager');
        $sharedEvents = $events->getSharedManager();
        $appEvents    = $this->application->getEventManager();
        $this->assertInstanceOf(EventManager::class, $appEvents);
        $this->assertNotSame($events, $appEvents);
        $this->assertSame($sharedEvents, $appEvents->getSharedManager());
    }

    public function testEventManagerListensOnApplicationContext()
    {
        $events      = $this->application->getEventManager();
        $identifiers = $events->getIdentifiers();
        $expected    = [Application::class];
        $this->assertEquals($expected, array_values($identifiers));
    }

    public function testServiceManagerIsPopulated()
    {
        $this->assertSame($this->serviceManager, $this->application->getContainer());
    }

    public function testConfigIsPopulated()
    {
        $smConfig  = $this->serviceManager->get('config');
        $appConfig = $this->application->getConfig();
        $this->assertEquals(
            $smConfig,
            $appConfig,
            sprintf('SM config: %s; App config: %s', var_export($smConfig, true), var_export($appConfig, true))
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

    /**
     * @param string $listenerServiceName
     * @param string $event
     * @param string $method
     *
     * @dataProvider bootstrapRegistersListenersProvider
     */
    public function testBootstrapRegistersListeners($listenerServiceName, $event, $method, $isCustom = false)
    {
        $listenerService = $this->serviceManager->get($listenerServiceName);
        $this->application->bootstrap($isCustom ? (array) $listenerServiceName : []);
        $events = $this->application->getEventManager();

        $foundListener = false;
        $listeners = $this->getArrayOfListenersForEvent($event, $events);
        $this->assertContains([$listenerService, $method], $listeners);
    }

    public function bootstrapRegistersListenersProvider()
    {
        // @codingStandardsIgnoreStart
        //                     [ Service Name,           Event,                       Method,        isCustom ]
        return [
            'route'         => ['RouteListener'        , MvcEvent::EVENT_ROUTE     , 'onRoute',      false],
            'dispatch'      => ['DispatchListener'     , MvcEvent::EVENT_DISPATCH  , 'onDispatch',   false],
            'middleware'    => ['MiddlewareListener'   , MvcEvent::EVENT_DISPATCH  , 'onDispatch',   false],
            'view_manager'  => ['ViewManager'          , MvcEvent::EVENT_BOOTSTRAP , 'onBootstrap',  false],
            'http_method'   => ['HttpMethodListener'   , MvcEvent::EVENT_ROUTE     , 'onRoute',      false],
            'bootstrap'     => ['BootstrapListener'    , MvcEvent::EVENT_BOOTSTRAP , 'onBootstrap',  true ],
        ];
        // @codingStandardsIgnoreEnd
    }

    public function testBootstrapAlwaysRegistersDefaultListeners()
    {
        $r = new ReflectionProperty($this->application, 'defaultListeners');
        $r->setAccessible(true);
        $defaultListenersNames = $r->getValue($this->application);
        $defaultListeners = [];
        foreach ($defaultListenersNames as $defaultListenerName) {
            $defaultListeners[] = $this->serviceManager->get($defaultListenerName);
        }

        $this->application->bootstrap(['BootstrapListener']);
        $eventManager = $this->application->getEventManager();

        $registeredListeners = [];
        foreach ($this->getEventsFromEventManager($eventManager) as $event) {
            foreach ($this->getListenersForEvent($event, $eventManager) as $listener) {
                if (is_array($listener)) {
                    $listener = array_shift($listener);
                }
                $registeredListeners[] = $listener;
            }
        }

        foreach ($defaultListeners as $defaultListener) {
            $this->assertContains($defaultListener, $registeredListeners);
        }
    }

    public function testBootstrapRegistersConfiguredMvcEvent()
    {
        $this->assertNull($this->application->getMvcEvent());
        $this->application->bootstrap();
        $event = $this->application->getMvcEvent();
        $this->assertInstanceOf(MvcEvent::class, $event);

        $router   = $this->serviceManager->get('HttpRouter');

        $this->assertFalse($event->isError());
        $this->assertNull($event->getRequest());
        $this->assertNull($event->getResponse());
        $this->assertSame($router, $event->getRouter());
        $this->assertSame($this->application, $event->getApplication());
        $this->assertSame($this->application, $event->getTarget());
    }

    public function setupPathController($addService = true)
    {
        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Route\Literal::factory([
            'route'    => '/path',
            'defaults' => [
                'controller' => 'path',
            ],
        ]);
        $router->addRoute('path', $route);
        $this->serviceManager->setService('HttpRouter', $router);
        $this->serviceManager->setService('Router', $router);

        if ($addService) {
            $this->services->addFactory('ControllerManager', function ($services) {
                return new ControllerManager($services, ['factories' => [
                    'path' => function () {
                        return new TestAsset\PathController;
                    },
                ]]);
            });
        }

        $this->application->bootstrap();
        return $this->application;
    }

    public function setupActionController()
    {
        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Route\Literal::factory([
            'route'    => '/sample',
            'defaults' => [
                'controller' => 'sample',
                'action'     => 'test',
            ],
        ]);
        $router->addRoute('sample', $route);

        $this->serviceManager->setFactory('ControllerManager', function ($services) {
            return new ControllerManager($services, ['factories' => [
                'sample' => function () {
                    return new Controller\TestAsset\SampleController();
                },
            ]]);
        });

        $this->application->bootstrap();
        return $this->application;
    }

    public function setupBadController($addService = true, $action = 'test')
    {
        $router = $this->serviceManager->get('HttpRouter');
        $route  = Router\Route\Literal::factory([
            'route'    => '/bad',
            'defaults' => [
                'controller' => 'bad',
                'action'     => $action,
            ],
        ]);
        $router->addRoute('bad', $route);

        if ($addService) {
            $this->serviceManager->setFactory('ControllerManager', function ($services) {
                return new ControllerManager($services, ['factories' => [
                    'bad' => function () {
                        return new Controller\TestAsset\BadController();
                    },
                ]]);
            });
        }

        $this->application->bootstrap();
        return $this->application;
    }

    public function testFinishEventIsTriggeredAfterDispatching()
    {
        $application = $this->setupActionController();
        $application->getEventManager()->attach(MvcEvent::EVENT_FINISH, function ($e) {
            $response = new Response();
            $response->getBody()->write('foobar');
            $e->setResponse($response);
        });

        $request = new ServerRequest([], [], 'http://example.local/sample', 'GET', 'php://memory');

        $application->run($request);
        $this->assertContains(
            'foobar',
            $this->application->getMvcEvent()->getResponse()->getBody()->__toString(),
            'The "finish" event was not triggered ("foobar" not in response)'
        );
    }

    /**
     * @group error-handling
     */
    public function testRoutingFailureShouldTriggerDispatchError()
    {
        $application = $this->setupBadController();
        $router      = new Router\SimpleRouteStack();
        $event       = $application->getMvcEvent();
        $event->setRouter($router);

        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $error      = $e->getError();
            $response = new Response();
            $response->getBody()->write("Code: " . $error);
            $e->setResponse($response);
            return $response;
        });

        $request = new ServerRequest([], [], 'http://example.local/bad', 'GET', 'php://memory');

        $application->run($request);
        $this->assertTrue($event->isError());
        $this->assertContains(
            Application::ERROR_ROUTER_NO_MATCH,
            $application->getMvcEvent()->getResponse()->getBody()->__toString()
        );
    }

    /**
     * @group error-handling
     */
    public function testLocatorExceptionShouldTriggerDispatchError()
    {
        $application = $this->setupPathController(false);
        $controllerLoader = $application->getContainer()->get('ControllerManager');
        $response = new Response();
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            return $response;
        });

        $request = new ServerRequest([], [], 'http://example.local/path', 'GET', 'php://memory');

        $resultResponse = $application->handle($request);
        $this->assertSame($response, $resultResponse);
    }

    /**
     * @requires PHP 7.0
     * @group error-handling
     */
    public function testPhp7ErrorRaisedInDispatchableShouldRaiseDispatchErrorEvent()
    {
        $this->setupBadController(true, 'test-php7-error');
        $events   = $this->application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $exception = $e->getParam('exception');
            $response = new Response();
            $response->getBody()->write($exception->getMessage());
            return $response;
        });

        $request = new ServerRequest([], [], 'http://example.local/bad', 'GET', 'php://memory');

        $response = $this->application->handle($request);
        $this->assertContains('Raised an error', $response->getBody()->__toString());
    }

    /**
     * @group error-handling
     */
    public function testFailureForRouteToReturnRouteMatchShouldPopulateEventError()
    {
        $application = $this->setupBadController();
        $router      = new Router\SimpleRouteStack();
        $event       = $application->getMvcEvent();
        $event->setRouter($router);

        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $error      = $e->getError();
            $response = new Response();
            $response->getBody()->write("Code: " . $error);
            return $response;
        });

        $request = new ServerRequest([], [], 'http://example.local/bad', 'GET', 'php://memory');
        $application->handle($request);
        $this->assertTrue($event->isError());
        $this->assertEquals(Application::ERROR_ROUTER_NO_MATCH, $event->getError());
    }

    /**
     * @group ZF2-171
     */
    public function testFinishShouldRunEvenIfRouteEventReturnsResponse()
    {
        $this->application->bootstrap();
        $events   = $this->application->getEventManager();
        $events->attach(MvcEvent::EVENT_ROUTE, function ($e) {
            return new Response();
        }, 100);

        $token = new stdClass;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($token) {
            $token->foo = 'bar';
        });

        $this->application->run();
        $this->assertTrue(isset($token->foo));
        $this->assertEquals('bar', $token->foo);
    }

    /**
     * @group ZF2-171
     */
    public function testFinishShouldRunEvenIfDispatchEventReturnsResponse()
    {
        $this->application->bootstrap();
        $events   = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_ROUTE);
        $events->attach(MvcEvent::EVENT_DISPATCH, function ($e) {
            return new Response();
        }, 100);

        $token = new stdClass;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($token) {
            $token->foo = 'bar';
        });

        $this->application->run();
        $this->assertTrue(isset($token->foo));
        $this->assertEquals('bar', $token->foo);
    }

    public function testApplicationShouldBeEventTargetAtFinishEvent()
    {
        $application = $this->setupActionController();

        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) {
            $response = new Response();
            $response->getBody()->write("EventClass: " . get_class($e->getTarget()));
            $e->setResponse($response);
        });

        $request = new ServerRequest([], [], 'http://example.local/sample', 'GET', 'php://memory');
        $response = $application->handle($request);
        $this->assertContains(Application::class, $response->getBody()->__toString());
    }

    public function testOnDispatchErrorEventPassedToTriggersShouldBeTheOriginalOne()
    {
        $application = $this->setupPathController(false);
        $model = $this->createMock(ViewModel::class);
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($model) {
            $e->setResult($model);
        });

        $application->run();
        $event = $application->getMvcEvent();
        $this->assertInstanceOf(ViewModel::class, $event->getResult());
    }

    /**
     * @group 2981
     */
    public function testReturnsResponseFromListenerWhenRouteEventShortCircuits()
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $events   = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_DISPATCH);
        $events->attach(MvcEvent::EVENT_ROUTE, function ($e) use ($testResponse) {
            $testResponse->getBody()->write('triggered');
            return $testResponse;
        }, 100);

        $triggered = false;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($testResponse, &$triggered) {
            $this->assertSame($testResponse, $e->getResponse());
            $triggered = true;
        });

        $this->application->run();
        $this->assertTrue($triggered);
    }

    /**
     * @group 2981
     */
    public function testReturnsResponseFromListenerWhenDispatchEventShortCircuits()
    {
        $this->application->bootstrap();
        $testResponse = new Response();
        $events   = $this->application->getEventManager();
        $events->clearListeners(MvcEvent::EVENT_ROUTE);
        $events->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($testResponse) {
            $testResponse->getBody()->write('triggered');
            return $testResponse;
        }, 100);

        $triggered = false;
        $events->attach(MvcEvent::EVENT_FINISH, function ($e) use ($testResponse, &$triggered) {
            $this->assertSame($testResponse, $e->getResponse());
            $triggered = true;
        });

        $this->application->run();
        $this->assertTrue($triggered);
    }

    public function testFailedRoutingShouldBePreventable()
    {
        $this->application->bootstrap();

        $response     = $this->createMock(ResponseInterface::class);
        $finishMock   = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $routeMock    = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $dispatchMock = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $routeMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
            $request = $event->getRequest();
            $event->setRequest(
                $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch([]))
            );
        }));
        $dispatchMock->expects($this->once())->method('__invoke')->will($this->returnValue($response));
        $finishMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
        }));

        $this->application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $routeMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, $dispatchMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_FINISH, $finishMock, 100);

        $this->application->run();
        $this->assertSame($response, $this->application->getMvcEvent()->getResponse());
    }

    public function testCanRecoverFromApplicationError()
    {
        $this->application->bootstrap();

        $response     = $this->createMock(ResponseInterface::class);
        $errorMock    = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $finishMock   = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $routeMock    = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
        $dispatchMock = $this->getMockBuilder(stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $errorMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
            $request = $event->getRequest();
            $event->setRequest(
                $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch([]))
            );
            $event->setError('');
        }));
        $routeMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
            $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
            $event->setError(Application::ERROR_ROUTER_NO_MATCH);
            return $event->getApplication()->getEventManager()->triggerEvent($event)->last();
        }));
        $dispatchMock->expects($this->once())->method('__invoke')->will($this->returnValue($response));
        $finishMock->expects($this->once())->method('__invoke')->will($this->returnCallback(function (MvcEvent $event) {
            $event->stopPropagation(true);
        }));

        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, $errorMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, $routeMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, $dispatchMock, 100);
        $this->application->getEventManager()->attach(MvcEvent::EVENT_FINISH, $finishMock, 100);

        $this->application->run();
        $this->assertSame($response, $this->application->getMvcEvent()->getResponse());
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
        $event = new MvcEvent();
        $event->setTarget($this->application);
        $event->setApplication($this->application);
        $event->setRouter($this->serviceManager->get('Router'));
        $event->stopPropagation(true);

        // Intentionally not calling bootstrap; setting mvc event
        $r = new ReflectionProperty($this->application, 'event');
        $r->setAccessible(true);
        $r->setValue($this->application, $event);

        // Setup listeners that stop propagation, but do nothing else
        $marker = [];
        foreach ($events as $event) {
            $marker[$event] = true;
        }
        $marker = (object) $marker;
        $listener = function ($e) use ($marker) {
            $marker->{$e->getName()} = $e->propagationIsStopped();
            $e->stopPropagation(true);
        };
        foreach ($events as $event) {
            $this->application->getEventManager()->attach($event, $listener);
        }

        $this->application->run();

        foreach ($events as $event) {
            $this->assertFalse($marker->{$event}, sprintf('Assertion failed for event "%s"', $event));
        }
    }
}
