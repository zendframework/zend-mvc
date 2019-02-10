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
use stdClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\Bootstrapper\ListenerProvider;
use Zend\Mvc\Exception\DomainException;
use Zend\Mvc\Exception\InvalidArgumentException;
use ZendTest\Mvc\ContainerTrait;

use function sprintf;

/**
 * @covers \Zend\Mvc\Bootstrapper\ListenerProvider
 */
class ListenerProviderTest extends TestCase
{
    use ContainerTrait;

    public function testRegistersListenersOnBootstrap()
    {
        $events      = new EventManager();
        $application = $this->prophesize(ApplicationInterface::class);
        $application->getEventManager()
            ->willReturn($events);

        $container = $this->mockContainerInterface();
        $listeners = [];

        $instanceListenerAggregate = $this->prophesize(ListenerAggregateInterface::class);
        $instanceListenerAggregate->attach($events)
            ->shouldBeCalled();
        $listeners[] = $instanceListenerAggregate->reveal();

        $serviceKeyAggregate = $this->prophesize(ListenerAggregateInterface::class);
        $serviceKeyAggregate->attach($events)
            ->shouldBeCalled();
        $this->injectServiceInContainer($container, 'listener_key', $serviceKeyAggregate->reveal());
        $listeners[] = 'listener_key';

        $bootstrapper = new ListenerProvider($container->reveal(), $listeners);

        $bootstrapper->bootstrap($application->reveal());
    }

    public function testDoesNotPullListenersFromContainerOnInstantiation()
    {
        $container = $this->mockContainerInterface();
        $container->get(Argument::any())
            ->shouldNotBeCalled();

        new ListenerProvider($container->reveal(), ['some_listener_key']);
    }

    public function testFailsIfListenerIsMissingFromContainer()
    {
        $events      = new EventManager();
        $application = $this->prophesize(ApplicationInterface::class);
        $application->getEventManager()
            ->willReturn($events);

        $container = $this->mockContainerInterface();
        $exception = $this->prophesize(NotFoundExceptionInterface::class)
            ->willExtend(Exception::class)
            ->reveal();
        $container->get('some_listener_key')
            ->willThrow($exception);

        $bootstrapper = new ListenerProvider($container->reveal(), ['some_listener_key']);

        $this->expectExceptionObject($exception);
        $bootstrapper->bootstrap($application->reveal());
    }

    /**
     * @dataProvider invalidListenerValues
     */
    public function testRejectsInvalidListenerListOnInstantiation($invalidValue)
    {
        $container = $this->mockContainerInterface();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'String service key or instance of %s',
            ListenerAggregateInterface::class
        ));

        new ListenerProvider($container->reveal(), [$invalidValue]);
    }

    /**
     * @dataProvider invalidListenerValues
     */
    public function testRejectsInvalidListenerFromContainer($invalidValue)
    {
        $container = $this->mockContainerInterface();
        $this->injectServiceInContainer($container, 'wrong_service', $invalidValue);

        $events      = new EventManager();
        $application = $this->prophesize(ApplicationInterface::class);
        $application->getEventManager()
            ->willReturn($events);

        $bootstrapper = new ListenerProvider($container->reveal(), ['wrong_service']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(sprintf(
            'instance of %s',
            ListenerAggregateInterface::class
        ));

        $bootstrapper->bootstrap($application->reveal());
    }

    public function invalidListenerValues()
    {
        return [
            'integer'  => [1],
            'null'     => [null],
            'callable' => [
                function () {
                },
            ],
            'object'   => [new stdClass()],
        ];
    }
}
