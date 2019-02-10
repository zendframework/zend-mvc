<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Bootstrapper;

use PHPUnit\Framework\TestCase;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\Bootstrapper\BootstrapEmitter;
use Zend\Mvc\MvcEvent;

/**
 * @covers \Zend\Mvc\Bootstrapper\BootstrapEmitter
 */
class BootstrapEmitterTest extends TestCase
{
    public function testEmitsBootstrapEvent()
    {
        $application = $this->prophesize(ApplicationInterface::class);

        $mvcEvent = new MvcEvent();
        $mvcEvent->setApplication($application->reveal());
        $application->getMvcEvent()
            ->willReturn($mvcEvent);

        $events = new EventManager();
        $application->getEventManager()
            ->willReturn($events);

        $bootstrapper = new BootstrapEmitter();

        $listener = $this->createPartialMock(stdClass::class, ['__invoke']);
        $listener->expects($this->once())
            ->method('__invoke')
            ->with($mvcEvent);

        $events->attach(MvcEvent::EVENT_BOOTSTRAP, $listener);
        $bootstrapper->bootstrap($application->reveal());
    }

    public function testSetsApplicationAsTarget()
    {
        $application = $this->prophesize(ApplicationInterface::class);

        $mvcEvent = new MvcEvent();
        $mvcEvent->setApplication($application->reveal());
        $application->getMvcEvent()
            ->willReturn($mvcEvent);

        $events = new EventManager();
        $application->getEventManager()
            ->willReturn($events);

        $bootstrapper = new BootstrapEmitter();

        $events->attach(MvcEvent::EVENT_BOOTSTRAP, function (MvcEvent $event) use ($application) {
            $this->assertSame($application->reveal(), $event->getTarget());
        });
        $bootstrapper->bootstrap($application->reveal());
    }
}
