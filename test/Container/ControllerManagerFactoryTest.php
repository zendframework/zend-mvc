<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\Mvc\Container\ControllerManagerFactory;
use Zend\Mvc\Controller\Dispatchable;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ControllerManagerFactory
 */
class ControllerManagerFactoryTest extends TestCase
{
    use ContainerTrait;

    /**
     * @var ObjectProphecy
     */
    private $container;

    /**
     * @var ControllerManagerFactory
     */
    private $factory;

    public function setUp()
    {
        $this->container = $this->mockContainerInterface();
        $this->factory = new ControllerManagerFactory();
    }

    public function testInjectsContainerIntoControllerManager()
    {
        $container = $this->container->reveal();
        $controllerManager = $this->factory->__invoke($container);
        $controllerManager->setFactory('Foo', function ($injectedContainer) use ($container) {
            $this->assertSame($container, $injectedContainer);
            return $this->prophesize(Dispatchable::class)->reveal();
        });
        $controllerManager->get('Foo');
    }

    public function testPullsControllersConfigFromConfigService()
    {
        $this->injectServiceInContainer($this->container, 'config', [
            'controllers' => [
                'factories' => [
                    'Foo' => function () {
                    },
                ]
            ]
        ]);
        $controllerManager = $this->factory->__invoke($this->container->reveal());
        $this->assertTrue($controllerManager->has('Foo'));
    }
}
