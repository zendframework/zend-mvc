<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc;

use Interop\Container\ContainerInterface;
use Interop\Http\Server\MiddlewareInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Application;
use Zend\Mvc\Controller\Dispatchable;
use Zend\Mvc\Exception\InvalidMiddlewareException;
use Zend\Mvc\Exception\ReachedFinalHandlerException;
use Zend\Mvc\MiddlewareListener;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteResult;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\ModelInterface;

class MiddlewareListenerTest extends TestCase
{
    /**
     * Create an MvcEvent, populated with everything it needs.
     *
     * @param string $middlewareMatched Middleware service matched by routing
     * @param mixed $middleware Value to return for middleware service
     * @param array $matchedParams
     * @return MvcEvent
     */
    public function createMvcEvent($middlewareMatched, $middleware = null, array $matchedParams = [])
    {
        $response   = new Response();
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $matchedParams['middleware'] = $middlewareMatched;
        $routeResult = RouteResult::fromRouteMatch($matchedParams);
        $request = $request->withAttribute(RouteResult::class, $routeResult);

        $eventManager   = new EventManager();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () {
                    return new EventManager();
                },
            ],
            'services' => [
                $middlewareMatched => $middleware,
            ],
        ]);

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getContainer()->willReturn($serviceManager);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);
        $event->setApplication($application->reveal());

        return $event;
    }

    public function testSuccessfullyDispatchesMiddleware()
    {
        $event = $this->createMvcEvent('path', function ($request, $response) {
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $response->getBody()->write('Test!');
            return $response;
        });
        $application = $event->getApplication();

        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertInstanceOf(Response::class, $return);

        $this->assertInstanceOf(Response::class, $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals('Test!', $return->getBody());
    }

    public function testSuccessfullyDispatchesHttpInteropMiddleware()
    {
        $expectedOutput = uniqid('expectedOutput', true);

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())->method('process')->willReturn(new HtmlResponse($expectedOutput));

        $event = $this->createMvcEvent('path', $middleware);
        $application = $event->getApplication();

        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertInstanceOf(Response::class, $return);

        $this->assertInstanceOf(Response::class, $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals($expectedOutput, $return->getBody());
    }

    public function testSuccessfullyDispatchesPipeOfCallableAndHttpInteropStyleMiddlewares()
    {
        $response   = new Response();
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch([
            'middleware' => [
                'firstMiddleware',
                'secondMiddleware',
            ],
        ]);
        $request = $request->withAttribute(RouteResult::class, $routeResult);

        $eventManager = new EventManager();

        $serviceManager = $this->prophesize(ContainerInterface::class);
        $serviceManager->get('EventManager')->willReturn($eventManager);
        $serviceManager->has('firstMiddleware')->willReturn(true);
        $serviceManager->get('firstMiddleware')->willReturn(function ($request, $response, $next) {
            $this->assertInstanceOf(ServerRequestInterface::class, $request);
            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->assertTrue(is_callable($next));
            return $next($request->withAttribute('firstMiddlewareAttribute', 'firstMiddlewareValue'), $response);
        });

        $secondMiddleware = $this->createMock(MiddlewareInterface::class);
        $secondMiddleware->expects($this->once())
            ->method('process')
            ->willReturnCallback(function (ServerRequestInterface $request) {
                return new HtmlResponse($request->getAttribute('firstMiddlewareAttribute'));
            });

        $serviceManager->has('secondMiddleware')->willReturn(true);
        $serviceManager->get('secondMiddleware')->willReturn($secondMiddleware);

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getContainer()->will(function () use ($serviceManager) {
            return $serviceManager->reveal();
        });

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);
        $event->setApplication($application->reveal());

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertInstanceOf(Response::class, $return);

        $this->assertInstanceOf(ResponseInterface::class, $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals('firstMiddlewareValue', $return->getBody()->__toString());
    }

    public function testTriggersErrorForUncallableMiddleware()
    {
        $event       = $this->createMvcEvent('path');
        $application = $event->getApplication();

        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->assertEquals(Application::ERROR_MIDDLEWARE_CANNOT_DISPATCH, $e->getError());
            $this->assertEquals('path', $e->getController());
            return 'FAILED';
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    public function testTriggersErrorForExceptionRaisedInMiddleware()
    {
        $exception   = new \Exception();
        $event       = $this->createMvcEvent('path', function ($request, $response) use ($exception) {
            throw $exception;
        });

        $application = $event->getApplication();
        $application->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($exception) {
            $this->assertEquals(Application::ERROR_EXCEPTION, $e->getError());
            $this->assertSame($exception, $e->getParam('exception'));
            return 'FAILED';
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    /**
     * Ensure that the listener tests for services in abstract factories.
     */
    public function testCanLoadFromAbstractFactory()
    {
        $response   = new Response();
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch(['middleware' => 'test']);
        $request = $request->withAttribute(RouteResult::class, $routeResult);

        $eventManager = new EventManager();

        $serviceManager = new ServiceManager();
        $serviceManager->addAbstractFactory(TestAsset\MiddlewareAbstractFactory::class);
        $serviceManager->setFactory(
            'EventManager',
            function () {
                return new EventManager();
            }
        );

        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getContainer()->willReturn($serviceManager);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);
        $event->setApplication($application->reveal());

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->fail(sprintf('dispatch.error triggered when it should not be: %s', var_export($e->getError(), 1)));
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);

        $this->assertInstanceOf(Response::class, $return);
        $this->assertSame(200, $return->getStatusCode());
        $this->assertEquals(TestAsset\Middleware::class, $return->getBody());
    }

    public function testMiddlewareWithNothingPipedReachesFinalHandlerException()
    {
        $response   = new Response();
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch(['middleware' => []]);
        $request = $request->withAttribute(RouteResult::class, $routeResult);

        $eventManager = new EventManager();

        $serviceManager = $this->prophesize(ContainerInterface::class);
        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getContainer()->will(function () use ($serviceManager) {
            return $serviceManager->reveal();
        });

        $serviceManager->get('EventManager')->willReturn($eventManager);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);
        $event->setApplication($application->reveal());

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->assertEquals(Application::ERROR_EXCEPTION, $e->getError());
            $this->assertInstanceOf(ReachedFinalHandlerException::class, $e->getParam('exception'));
            return 'FAILED';
        });

        $listener = new MiddlewareListener();
        $return   = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    public function testNullMiddlewareThrowsInvalidMiddlewareException()
    {
        $response   = new Response();
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch(['middleware' => [null]]);
        $request = $request->withAttribute(RouteResult::class, $routeResult);

        $eventManager = new EventManager();

        $serviceManager = $this->prophesize(ContainerInterface::class);
        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);
        $application->getContainer()->will(function () use ($serviceManager) {
            return $serviceManager->reveal();
        });

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse($response);
        $event->setApplication($application->reveal());

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $this->assertEquals(Application::ERROR_MIDDLEWARE_CANNOT_DISPATCH, $e->getError());
            $this->assertInstanceOf(InvalidMiddlewareException::class, $e->getParam('exception'));
            return 'FAILED';
        });

        $listener = new MiddlewareListener();

        $return = $listener->onDispatch($event);
        $this->assertEquals('FAILED', $return);
    }

    public function testValidMiddlewareDispatchCancelsPreviousDispatchFailures()
    {
        $middlewareName = uniqid('middleware', true);
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch(['middleware' => $middlewareName]);
        $request = $request->withAttribute(RouteResult::class, $routeResult);
        $response       = new Response();
        /* @var $application Application|\PHPUnit_Framework_MockObject_MockObject */
        $application    = $this->createMock(Application::class);
        $eventManager   = new EventManager();
        $middleware     = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () {
                    return new EventManager();
                },
            ],
            'services' => [
                $middlewareName => $middleware,
            ],
        ]);

        $application->expects(self::any())->method('getEventManager')->willReturn($eventManager);
        $application->expects(self::any())->method('getContainer')->willReturn($serviceManager);
        $middleware->expects(self::once())->method('__invoke')->willReturn($response);

        $event = new MvcEvent();

        $event->setRequest($request);
        $event->setApplication($application);
        $event->setError(Application::ERROR_CONTROLLER_CANNOT_DISPATCH);

        $listener = new MiddlewareListener();
        $result   = $listener->onDispatch($event);

        self::assertInstanceOf(Response::class, $result);
        self::assertInstanceOf(Response::class, $event->getResult());
        self::assertEmpty($event->getError(), 'Previously set MVC errors are canceled by a successful dispatch');
    }

    public function testValidMiddlewareFiresDispatchableInterfaceEventListeners()
    {
        $middlewareName = uniqid('middleware', true);
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch(['middleware' => $middlewareName]);
        $request = $request->withAttribute(RouteResult::class, $routeResult);
        $response       = new Response();
        /* @var $application Application|\PHPUnit_Framework_MockObject_MockObject */
        $application    = $this->createMock(Application::class);
        $sharedManager  = new SharedEventManager();
        /* @var $sharedListener callable|\PHPUnit_Framework_MockObject_MockObject */
        $sharedListener = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $eventManager   = new EventManager();
        $middleware     = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () use ($sharedManager) {
                    return new EventManager($sharedManager);
                },
            ],
            'services' => [
                $middlewareName => $middleware,
            ],
        ]);

        $application->expects(self::any())->method('getEventManager')->willReturn($eventManager);
        $application->expects(self::any())->method('getContainer')->willReturn($serviceManager);
        $middleware->expects(self::once())->method('__invoke')->willReturn($response);

        $event = new MvcEvent();

        $event->setRequest($request);
        $event->setApplication($application);
        $event->setError(Application::ERROR_CONTROLLER_CANNOT_DISPATCH);

        $listener = new MiddlewareListener();

        $sharedManager->attach(Dispatchable::class, MvcEvent::EVENT_DISPATCH, $sharedListener, 9001);
        $sharedListener->expects(self::once())->method('__invoke')->with($event);

        $listener->onDispatch($event);
    }

    /**
     * @dataProvider alreadySetMvcEventResultProvider
     *
     * @param mixed $alreadySetResult
     */
    public function testWillNotDispatchWhenAnMvcEventResultIsAlreadySet($alreadySetResult)
    {
        $middlewareName = uniqid('middleware', true);
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch(['middleware' => $middlewareName]);
        $request = $request->withAttribute(RouteResult::class, $routeResult);
        /* @var $application Application|\PHPUnit_Framework_MockObject_MockObject */
        $application    = $this->createMock(Application::class);
        $eventManager   = new EventManager();
        $middleware     = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $serviceManager = new ServiceManager([
            'factories' => [
                'EventManager' => function () {
                    return new EventManager();
                },
            ],
            'services' => [
                $middlewareName => $middleware,
            ],
        ]);

        $application->expects(self::any())->method('getEventManager')->willReturn($eventManager);
        $application->expects(self::any())->method('getContainer')->willReturn($serviceManager);
        $middleware->expects(self::never())->method('__invoke');

        $event = new MvcEvent();

        $event->setResult($alreadySetResult); // a result is already there - listener should bail out early
        $event->setRequest($request);
        $event->setApplication($application);
        $event->setError(Application::ERROR_CONTROLLER_CANNOT_DISPATCH);

        $listener = new MiddlewareListener();

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function () {
            self::fail('No dispatch failures should be raised - dispatch should be skipped');
        });

        $listener->onDispatch($event);

        self::assertSame($alreadySetResult, $event->getResult(), 'The event result was not replaced');
    }

    /**
     * @return mixed[][]
     */
    public function alreadySetMvcEventResultProvider()
    {
        return [
            [123],
            [true],
            [false],
            [[]],
            [new \stdClass()],
            [$this],
            [$this->createMock(ModelInterface::class)],
            [$this->createMock(ResponseInterface::class)],
            [['view model data' => 'as an array']],
            [['foo' => new \stdClass()]],
            ['a response string'],
        ];
    }
}
