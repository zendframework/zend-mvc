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
use Zend\EventManager\EventManager;
use Zend\Mvc\Application;
use Zend\Mvc\Container\ApplicationFactory;
use PHPUnit\Framework\TestCase;
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
        $this->events = new EventManager();

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
}
