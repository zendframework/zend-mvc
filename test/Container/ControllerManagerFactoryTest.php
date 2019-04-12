<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Zend\Mvc\Container\ControllerManagerFactory;
use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Controller\ControllerManager;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ControllerManagerFactory
 */
class ControllerManagerFactoryTest extends TestCase
{
    use ContainerTrait;

    /** @var ObjectProphecy */
    private $container;

    /** @var ControllerManagerFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory   = new ControllerManagerFactory();
        $this->container = $this->mockContainerInterface();
    }

    public function testCanCreateControllerManager()
    {
        $controllers = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(ControllerManager::class, $controllers);
    }

    public function testCanConfigureFromMainConfigService()
    {
        $controllerMock        = $this->prophesize(AbstractController::class)->reveal();
        $config['controllers'] = [
            'services' => [
                'Foo' => $controllerMock,
            ],
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $controllers = $this->factory->__invoke($this->container->reveal());
        $this->assertSame($controllerMock, $controllers->get('Foo'));
    }

    public function testGetConfigReturnsControllersConfigFromMainConfigService()
    {
        $controllersConfig     = [
            'factories' => ['Foo' => 'Bar'],
        ];
        $config['controllers'] = $controllersConfig;
        $this->injectServiceInContainer($this->container, 'config', $config);

        $this->assertSame(
            $controllersConfig,
            ControllerManagerFactory::getConfig($this->container->reveal())
        );
    }

    public function testMainConfigServiceIsOptional()
    {
        $this->assertEmpty(ControllerManagerFactory::getConfig($this->container->reveal()));
    }

    public function testControllersConfigInMainConfigServiceIsOptional()
    {
        $config['bar'] = [
            'services' => [
                'Foo' => 'Bar',
            ],
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);
        $this->assertEmpty(ControllerManagerFactory::getConfig($this->container->reveal()));
    }
}
