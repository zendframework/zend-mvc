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
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManager;
use Zend\Mvc\Application;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\DispatchListener;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteResult;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\ModelInterface;

class DispatchListenerTest extends TestCase
{
    public function createMvcEvent(string $controllerMatched)
    {
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');

        $routeResult = RouteResult::fromRouteMatch(['controller' => $controllerMatched]);
        $request = $request->withAttribute(RouteResult::class, $routeResult);

        $eventManager = new EventManager();
        $application = $this->prophesize(Application::class);
        $application->getEventManager()->willReturn($eventManager);

        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setApplication($application->reveal());

        return $event;
    }

    public function testControllerManagerUsingAbstractFactory()
    {
        $controllerManager = new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\ControllerLoaderAbstractFactory::class,
        ]]);
        $listener = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $return = $listener->onDispatch($event);

        $this->assertEmpty($log, var_export($log, true));
        // @TODO should response be set in mvc event?
        // $this->assertSame($event->getResponse(), $return);
        $this->assertSame(200, $return->getStatusCode());
    }

    public function testUnlocatableControllerViaAbstractFactory()
    {
        $controllerManager = new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory::class,
        ]]);
        $listener = new DispatchListener($controllerManager);

        $event = $this->createMvcEvent('path');

        $log = [];
        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use (&$log) {
            $log['error'] = $e->getError();
        });

        $return = $listener->onDispatch($event);

        $this->assertArrayHasKey('error', $log);
        $this->assertSame('error-controller-not-found', $log['error']);
    }

    /**
     * @dataProvider alreadySetMvcEventResultProvider
     *
     * @param mixed $alreadySetResult
     */
    public function testWillNotDispatchWhenAnMvcEventResultIsAlreadySet($alreadySetResult)
    {
        $event = $this->createMvcEvent('path');

        $event->setResult($alreadySetResult);

        $listener = new DispatchListener(new ControllerManager(new ServiceManager(), ['abstract_factories' => [
            Controller\TestAsset\UnlocatableControllerLoaderAbstractFactory::class,
        ]]));

        $event->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH_ERROR, function () {
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
            [new Response()],
            [['view model data' => 'as an array']],
            [['foo' => new \stdClass()]],
            ['a response string'],
        ];
    }
}
