<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Controller\Dispatchable;
use Zend\Mvc\Controller\MiddlewareController;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\MvcEvent;
use Zend\Stratigility\MiddlewarePipe;

/**
 * @covers \Zend\Mvc\Controller\MiddlewareController
 */
class MiddlewareControllerTest extends TestCase
{
    /**
     * @var MiddlewarePipe|\PHPUnit_Framework_MockObject_MockObject
     */
    private $pipe;

    /**
     * @var ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $responsePrototype;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @var AbstractController|\PHPUnit_Framework_MockObject_MockObject
     */
    private $controller;

    /**
     * @var MvcEvent
     */
    private $event;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->pipe              = $this->createMock(MiddlewarePipe::class);
        $this->responsePrototype = $this->createMock(ResponseInterface::class);
        $this->eventManager      = $this->createMock(EventManagerInterface::class);
        $this->event             = new MvcEvent();
        $this->eventManager      = new EventManager();

        $this->controller = new MiddlewareController(
            $this->pipe,
            $this->responsePrototype,
            $this->eventManager,
            $this->event
        );
    }

    public function testWillAssignCorrectEventManagerIdentifiers()
    {
        $identifiers = $this->eventManager->getIdentifiers();

        self::assertContains(MiddlewareController::class, $identifiers);
        self::assertContains(AbstractController::class, $identifiers);
        self::assertContains(Dispatchable::class, $identifiers);
    }

    public function testWillDispatchARequestAndResponseWithAGivenPipe()
    {
        $request          = new ServerRequest([], [], null, 'GET', 'php://memory');
        $result           = $this->createMock(ResponseInterface::class);
        /* @var $dispatchListener callable|\PHPUnit_Framework_MockObject_MockObject */
        $dispatchListener = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();

        $this->eventManager->attach(MvcEvent::EVENT_DISPATCH, $dispatchListener, 100);
        $this->eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function () {
            self::fail('No dispatch error expected');
        }, 100);

        $dispatchListener
            ->expects(self::once())
            ->method('__invoke')
            ->with(self::callback(function (MvcEvent $event) use ($request) {
                self::assertSame($this->event, $event);
                self::assertSame(MvcEvent::EVENT_DISPATCH, $event->getName());
                self::assertSame($this->controller, $event->getTarget());
                self::assertSame($request, $event->getRequest());

                return true;
            }));

        $this->pipe->expects(self::once())->method('process')->willReturn($result);

        $controllerResult = $this->controller->dispatch($request);

        self::assertSame($result, $controllerResult);
        self::assertSame($result, $this->event->getResult());
    }
}
