<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use ReflectionClass;
use stdClass;
use Zend\EventManager\EventManager;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceManager;

/**
 * @covers \Zend\Mvc\Service\ServiceManagerConfig
 */
class ServiceManagerConfigTest extends TestCase
{
    /**
     * @var ServiceManagerConfig
     */
    private $config;

    /**
     * @var ServiceManager
     */
    private $services;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->config   = new ServiceManagerConfig();
        $this->services = new ServiceManager();
        $this->config->configureServiceManager($this->services);
    }

    /**
     * Create an event manager instance based on zend-eventmanager version
     *
     * @param null|\Zend\EventManager\SharedEventManagerInterface
     * @return EventManager
     */
    protected function createEventManager($sharedManager = null)
    {
        $r = new ReflectionClass(EventManager::class);

        if ($r->hasMethod('setSharedManager')) {
            $events = new EventManager();
            $events->setSharedManager($sharedManager ?: $this->services->get('SharedEventManager'));
            return $events;
        }

        return new EventManager($sharedManager ?: $this->services->get('SharedEventManager'));
    }

    /**
     * @group 3786
     */
    public function testEventManagerAwareInterfaceIsNotInjectedIfPresentButSharedManagerIs()
    {
        $events = $this->createEventManager();
        TestAsset\EventManagerAwareObject::$defaultEvents = $events;

        $this->services->setAlias('EventManagerAwareObject', TestAsset\EventManagerAwareObject::class);
        $this->services->setFactory(TestAsset\EventManagerAwareObject::class, InvokableFactory::class);

        $instance = $this->services->get('EventManagerAwareObject');
        $this->assertInstanceOf(TestAsset\EventManagerAwareObject::class, $instance);
        $this->assertSame($events, $instance->getEventManager());
        $this->assertSame($this->services->get('SharedEventManager'), $events->getSharedManager());
    }

    /**
     * @group 6266
     */
    public function testCanMergeCustomConfigWithDefaultConfig()
    {
        $custom = [
            'invokables' => [
                'foo' => stdClass::class,
            ],
            'factories' => [
                'bar' => function () {
                    return new stdClass();
                },
            ],
        ];

        $sm = new ServiceManager();
        (new ServiceManagerConfig($custom))->configureServiceManager($sm);

        $this->assertTrue($sm->has('foo'));
        $this->assertTrue($sm->has('bar'));
        $this->assertTrue($sm->has('ModuleManager'));
    }

    /**
     * @group 6266
     */
    public function testCanOverrideDefaultConfigWithCustomConfig()
    {
        $custom = [
            'invokables' => [
                'foo' => stdClass::class,
            ],
            'factories' => [
                'ModuleManager' => function () {
                    return new stdClass();
                },
            ],
        ];

        $sm = new ServiceManager();
        (new ServiceManagerConfig($custom))->configureServiceManager($sm);

        $this->assertTrue($sm->has('foo'));
        $this->assertTrue($sm->has('ModuleManager'));

        $this->assertInstanceOf(stdClass::class, $sm->get('ModuleManager'));
    }

    /**
     * @group 6266
     */
    public function testCanAddDelegators()
    {
        /*
         * Create delegator closure
         *
         * The signature for delegators differs between zend-servicemanager
         * v2 and v3, so we must vary the closure used based on the version
         * being used when testing.
         */
        if (method_exists($this->services, 'configure')) {
            // v3
            $delegator = function ($container, $name, $callback, array $options = null) {
                $service = $callback();
                $service->bar = 'baz';
                return $service;
            };
        } else {
            // v2
            $delegator = function ($container, $name, $requestedName, $callback) {
                $service = $callback();
                $service->bar = 'baz';
                return $service;
            };
        }

        $config = [
            'aliases' => [
                'foo' => stdClass::class,
            ],
            'factories' => [
                stdClass::class => InvokableFactory::class,
            ],
            'delegators' => [
                stdClass::class => [ $delegator ],
            ],
        ];

        $sm = new ServiceManager();
        (new ServiceManagerConfig($config))->configureServiceManager($sm);

        $std = $sm->get('foo');
        $this->assertInstanceOf(stdClass::class, $std);
        $this->assertEquals('baz', $std->bar);
    }

    /**
     * @group 6266
     */
    public function testEventManagerInitializerCanBeReplaced()
    {
        $instance       = $this->getMock('Zend\EventManager\EventManagerAwareInterface');
        $initializer    = $this->getMock(stdClass::class, ['__invoke']);
        $config         = new ServiceManagerConfig([
            'initializers' => [
                'EventManagerAwareInitializer' => $initializer,
            ],
            'factories' => [
                'EventManagerAware' => function () use ($instance) {
                    return $instance;
                },
            ],
        ]);
        $serviceManager = new ServiceManager();
        $config->configureServiceManager($serviceManager);

        /*
         * Need to vary the order of arguments the initializer receives based on
         * which zend-servicemanager version is being tested against.
         */
        if (method_exists($this->services, 'configure')) {
            // v3
            $initializer->expects($this->once())->method('__invoke')->with($serviceManager, $instance);
        } else {
            // v2
            $initializer->expects($this->once())->method('__invoke')->with($instance, $serviceManager);
        }

        $instance->expects($this->never())->method('getEventManager');
        $instance->expects($this->never())->method('setEventManager');

        $serviceManager->get('EventManagerAware');
    }
}
