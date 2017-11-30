<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\HttpMethodListener;
use Zend\Mvc\MvcEvent;

/**
 * @covers Zend\Mvc\HttpMethodListener
 */
class HttpMethodListenerTest extends TestCase
{
    /**
     * @var HttpMethodListener
     */
    protected $listener;

    public function setUp()
    {
        $this->listener = new HttpMethodListener();
    }

    public function testConstructor()
    {
        $methods = ['foo', 'bar'];
        $listener = new HttpMethodListener(false, $methods);

        $this->assertFalse($listener->isEnabled());
        $this->assertSame(['FOO', 'BAR'], $listener->getAllowedMethods());

        $listener = new HttpMethodListener(true, []);
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testAttachesToRouteEvent()
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $eventManager->expects($this->atLeastOnce())
                     ->method('attach')
                     ->with(MvcEvent::EVENT_ROUTE);

        $this->listener->attach($eventManager);
    }

    public function testDoesntAttachIfDisabled()
    {
        $this->listener->setEnabled(false);

        $eventManager = $this->createMock(EventManagerInterface::class);
        $eventManager->expects($this->never())
                     ->method('attach');

        $this->listener->attach($eventManager);
    }

    public function testOnRouteDoesNothingIfIfMethodIsAllowed()
    {
        $event = new MvcEvent();
        $request = new ServerRequest([], [], null, 'FOO', 'php://memory');
        $event->setRequest($request);

        $this->listener->setAllowedMethods(['foo']);

        $this->assertNull($this->listener->onRoute($event));
    }

    public function testOnRouteReturns405ResponseIfMethodNotAllowed()
    {
        $event = new MvcEvent();
        $request = new ServerRequest([], [], null, 'FOO', 'php://memory');
        $event->setRequest($request);

        $response = $this->listener->onRoute($event);

        $this->assertNotNull($response);
        $this->assertSame(405, $response->getStatusCode());
    }
}
