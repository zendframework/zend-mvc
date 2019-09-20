<?php

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ModuleManager\Feature\ControllerPluginProviderInterface;
use Zend\ModuleManager\Feature\ControllerProviderInterface;
use Zend\ModuleManager\Feature\RouteProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;
use Zend\ModuleManager\Listener\DefaultListenerAggregate;
use Zend\ModuleManager\Listener\ListenerOptions;
use Zend\ModuleManager\Listener\ServiceListenerInterface;
use Zend\ModuleManager\ModuleEvent;
use Zend\ModuleManager\ModuleManager;
use Zend\Mvc\Service\ModuleManagerFactory;
use PHPUnit\Framework\TestCase;

class ModuleManagerFactoryTest extends TestCase
{
    public function testFactory()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $serviceListener = $this->prophesize(ServiceListenerInterface::class);
        $eventManager = $this->prophesize(EventManagerInterface::class);

        $configuration = [
            'modules' => [
                'Foo',
            ],
            'module_listener_options' => [],
        ];
        $container->get('ApplicationConfig')
            ->willReturn($configuration);
        $container->get('ServiceListener')
            ->willReturn($serviceListener->reveal());
        $container->get('EventManager')
            ->willReturn($eventManager->reveal());

        $container->has(DefaultListenerAggregate::class)
            ->willReturn(false);
        $container->has(ListenerOptions::class)
            ->willReturn(false);

        $serviceListener->addServiceManager(
            $container->reveal(),
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ControllerManager',
            'controllers',
            ControllerProviderInterface::class,
            'getControllerConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ControllerPluginManager',
            'controller_plugins',
            ControllerPluginProviderInterface::class,
            'getControllerPluginConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ViewHelperManager',
            'view_helpers',
            ViewHelperProviderInterface::class,
            'getViewHelperConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'RoutePluginManager',
            'route_manager',
            RouteProviderInterface::class,
            'getRouteConfig'
        )
            ->shouldBeCalled();

        $serviceListener->attach($eventManager->reveal())
            ->shouldBeCalled();

        $factory = new ModuleManagerFactory();

        $service = $factory($container->reveal(), 'ModuleManager');

        $this->assertInstanceOf(ModuleManager::class, $service);
        $this->assertInstanceOf(ModuleEvent::class, $service->getEvent());
        $this->assertSame($container->reveal(), $service->getEvent()->getParam('ServiceManager'));
        $this->assertSame($eventManager->reveal(), $service->getEventManager());
        $this->assertSame($configuration['modules'], $service->getModules());
    }

    public function testFactoryWithListenerOptionsFromContainer()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $serviceListener = $this->prophesize(ServiceListenerInterface::class);
        $eventManager = $this->prophesize(EventManagerInterface::class);
        $listenerOptions = new ListenerOptions();

        $configuration = [
            'modules' => [
                'Foo',
            ],
            'module_listener_options' => [],
        ];
        $container->get('ApplicationConfig')
            ->willReturn($configuration);
        $container->get('ServiceListener')
            ->willReturn($serviceListener->reveal());
        $container->get('EventManager')
            ->willReturn($eventManager->reveal());

        $container->has(DefaultListenerAggregate::class)
            ->willReturn(false);
        $container->has(ListenerOptions::class)
            ->willReturn(true);

        $container->get(ListenerOptions::class)
            ->willReturn($listenerOptions);

        $serviceListener->addServiceManager(
            $container->reveal(),
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ControllerManager',
            'controllers',
            ControllerProviderInterface::class,
            'getControllerConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ControllerPluginManager',
            'controller_plugins',
            ControllerPluginProviderInterface::class,
            'getControllerPluginConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ViewHelperManager',
            'view_helpers',
            ViewHelperProviderInterface::class,
            'getViewHelperConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'RoutePluginManager',
            'route_manager',
            RouteProviderInterface::class,
            'getRouteConfig'
        )
            ->shouldBeCalled();

        $serviceListener->attach($eventManager->reveal())
            ->shouldBeCalled();

        $factory = new ModuleManagerFactory();

        $service = $factory($container->reveal(), 'ModuleManager');

        $this->assertInstanceOf(ModuleManager::class, $service);
        $this->assertInstanceOf(ModuleEvent::class, $service->getEvent());
        $this->assertSame($container->reveal(), $service->getEvent()->getParam('ServiceManager'));
        $this->assertSame($eventManager->reveal(), $service->getEventManager());
        $this->assertSame($configuration['modules'], $service->getModules());
    }

    public function testFactoryWithDefaultListenerAggregateFromContainer()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $serviceListener = $this->prophesize(ServiceListenerInterface::class);
        $eventManager = $this->prophesize(EventManagerInterface::class);
        $defaultListenerAggregate = $this->prophesize(DefaultListenerAggregate::class);

        $configuration = [
            'modules' => [
                'Foo',
            ],
            'module_listener_options' => [],
        ];
        $container->get('ApplicationConfig')
            ->willReturn($configuration);
        $container->get('ServiceListener')
            ->willReturn($serviceListener->reveal());
        $container->get('EventManager')
            ->willReturn($eventManager->reveal());

        $container->has(DefaultListenerAggregate::class)
            ->willReturn(true);

        $container->get(DefaultListenerAggregate::class)
            ->willReturn($defaultListenerAggregate->reveal());

        $serviceListener->addServiceManager(
            $container->reveal(),
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ControllerManager',
            'controllers',
            ControllerProviderInterface::class,
            'getControllerConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ControllerPluginManager',
            'controller_plugins',
            ControllerPluginProviderInterface::class,
            'getControllerPluginConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'ViewHelperManager',
            'view_helpers',
            ViewHelperProviderInterface::class,
            'getViewHelperConfig'
        )
            ->shouldBeCalled();

        $serviceListener->addServiceManager(
            'RoutePluginManager',
            'route_manager',
            RouteProviderInterface::class,
            'getRouteConfig'
        )
            ->shouldBeCalled();

        $defaultListenerAggregate->attach($eventManager->reveal())
            ->shouldBeCalled();
        $serviceListener->attach($eventManager->reveal())
            ->shouldBeCalled();

        $factory = new ModuleManagerFactory();

        $service = $factory($container->reveal(), 'ModuleManager');

        $this->assertInstanceOf(ModuleManager::class, $service);
        $this->assertInstanceOf(ModuleEvent::class, $service->getEvent());
        $this->assertSame($container->reveal(), $service->getEvent()->getParam('ServiceManager'));
        $this->assertSame($eventManager->reveal(), $service->getEventManager());
        $this->assertSame($configuration['modules'], $service->getModules());
    }
}
