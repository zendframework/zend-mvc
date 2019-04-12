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
use Zend\Mvc\Container\RoutePluginManagerFactory;
use Zend\Router\RouteInterface;
use Zend\Router\RoutePluginManager;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\RoutePluginManagerFactory
 */
class RoutePluginManagerFactoryTest extends TestCase
{
    use ContainerTrait;

    /** @var ObjectProphecy */
    private $container;

    /** @var RoutePluginManagerFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory   = new RoutePluginManagerFactory();
        $this->container = $this->mockContainerInterface();
    }

    public function testCanCreateRoutePluginManager()
    {
        $plugins = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(RoutePluginManager::class, $plugins);
    }

    public function testCanConfigureFromMainConfigService()
    {
        $routeMock               = $this->prophesize(RouteInterface::class)->reveal();
        $config['route_manager'] = [
            'services' => [
                'Foo' => $routeMock,
            ],
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $plugins = $this->factory->__invoke($this->container->reveal());
        $this->assertSame($routeMock, $plugins->get('Foo'));
    }

    public function testGetConfigReturnsConfigFromMainConfigService()
    {
        $pluginsConfig           = [
            'factories' => ['Foo' => 'Bar'],
        ];
        $config['route_manager'] = $pluginsConfig;
        $this->injectServiceInContainer($this->container, 'config', $config);

        $this->assertSame($pluginsConfig, $this->factory->getConfig($this->container->reveal()));
    }

    public function testMainConfigServiceIsOptional()
    {
        $this->assertEmpty($this->factory->getConfig($this->container->reveal()));
    }

    public function testRoutePluginsConfigInMainConfigServiceIsOptional()
    {
        $config['bar'] = [
            'services' => [
                'Foo' => 'Bar',
            ],
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);
        $this->assertEmpty($this->factory->getConfig($this->container->reveal()));
    }
}
