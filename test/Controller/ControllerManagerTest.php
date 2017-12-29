<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

declare(strict_types=1);

namespace ZendTest\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\Dispatchable;
use Zend\Mvc\Controller\PluginManager;
use Zend\ServiceManager\Exception\InvalidServiceException;
use ZendTest\Mvc\ContainerTrait;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

/**
 * @covers \Zend\Mvc\Controller\ControllerManager
 */
class ControllerManagerTest extends TestCase
{
    use ContainerTrait;

    /**
     * @var ObjectProphecy|ContainerInterface
     */
    private $container;

    /**
     * @var ObjectProphecy|PluginManager
     */
    private $plugins;

    /**
     * @var SharedEventManager
     */
    private $sharedEvents;

    /**
     * @var EventManager
     */
    private $events;

    /**
     * @var ControllerManager
     */
    private $controllers;

    /**
     * @var SampleController
     */
    private $controller;

    public function setUp()
    {
        $this->container = $this->mockContainerInterface();
        $this->plugins = $this->prophesize(PluginManager::class);
        $this->sharedEvents   = new SharedEventManager();
        $this->events = new EventManager($this->sharedEvents);

        $this->injectServiceInContainer($this->container, 'EventManager', $this->events);
        $this->injectServiceInContainer($this->container, PluginManager::class, $this->plugins->reveal());

        $this->controllers = new ControllerManager($this->container->reveal());
        $this->controller = new SampleController();
        $this->controllers->setFactory(SampleController::class, function () {
            return $this->controller;
        });
    }

    public function testCanInjectEventManager()
    {
        $this->container->get('EventManager')->shouldBeCalled();
        $controller = $this->controllers->get(SampleController::class);

        // The default AbstractController implementation lazy instantiates an EM
        // instance, which means we need to check that that instance gets injected
        // with the shared EM instance.
        $events = $controller->getEventManager();
        $this->assertSame($events, $this->events);
    }

    public function testCanInjectPluginManager()
    {
        $this->container->get(PluginManager::class)->shouldBeCalled();
        $controller = $this->controllers->get(SampleController::class);

        $this->assertSame($this->plugins->reveal(), $controller->getPluginManager());
    }

    public function testInjectEventManagerWillNotOverwriteExistingEventManagerIfItAlreadyHasASharedManager()
    {
        $events     = new EventManager($this->sharedEvents);
        $this->controller->setEventManager($events);

        $this->container->get('EventManager')->shouldNotBeCalled();
        $controller = $this->controllers->get(SampleController::class);

        $this->assertSame($events, $controller->getEventManager());
        $this->assertSame($this->sharedEvents, $events->getSharedManager());
    }

    public function testRequiresControllerToBeDispatchable()
    {
        $this->controllers->setFactory('Foo', function () {
            return new \stdClass;
        });

        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage(\sprintf('must implement %s', Dispatchable::class));
        $this->controllers->get('Foo');
    }

    public function testInitializersSkipIfDispatchableIsNotPluginOrEventAware()
    {
        $this->controllers->setFactory('Foo', function () {
            return $this->prophesize(Dispatchable::class)->reveal();
        });

        $this->controllers->get('Foo');
        $this->assertTrue(true, 'No assertions made, no errors expected');
    }
}
