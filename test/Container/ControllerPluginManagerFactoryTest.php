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
use Zend\Mvc\Container\ControllerPluginManagerFactory;
use Zend\Mvc\Controller\Plugin\PluginInterface;
use Zend\Mvc\Controller\PluginManager;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ControllerPluginManagerFactory
 */
class ControllerPluginManagerFactoryTest extends TestCase
{
    use ContainerTrait;

    /** @var ObjectProphecy */
    private $container;

    /** @var ControllerPluginManagerFactory */
    private $factory;

    protected function setUp() : void
    {
        $this->factory   = new ControllerPluginManagerFactory();
        $this->container = $this->mockContainerInterface();
    }

    public function testCanCreateControllerPluginManager()
    {
        $plugins = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf(PluginManager::class, $plugins);
    }

    public function testCanConfigureFromMainConfigService()
    {
        $pluginMock                   = $this->prophesize(PluginInterface::class)->reveal();
        $config['controller_plugins'] = [
            'services' => [
                'Foo' => $pluginMock,
            ],
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $plugins = $this->factory->__invoke($this->container->reveal());
        $this->assertSame($pluginMock, $plugins->get('Foo'));
    }

    public function testGetConfigReturnsConfigFromMainConfigService()
    {
        $pluginsConfig                = [
            'factories' => ['Foo' => 'Bar'],
        ];
        $config['controller_plugins'] = $pluginsConfig;
        $this->injectServiceInContainer($this->container, 'config', $config);

        $this->assertSame($pluginsConfig, $this->factory->getConfig($this->container->reveal()));
    }

    public function testMainConfigServiceIsOptional()
    {
        $this->assertEmpty($this->factory->getConfig($this->container->reveal()));
    }

    public function testControllerPluginsConfigInMainConfigServiceIsOptional()
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
