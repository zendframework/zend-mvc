<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager;
use Zend\Mvc\DispatchListener;
use Zend\Mvc\HttpMethodListener;
use Zend\Mvc\Service\ServiceListenerFactory;
use Zend\Mvc\View\Http\ExceptionStrategy;
use Zend\Mvc\View\Http\InjectTemplateListener;
use Zend\Mvc\View\Http\RouteNotFoundStrategy;
use Zend\Mvc\View\Http\ViewManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Renderer\RendererInterface;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\PrefixPathStackResolver;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Resolver\TemplateMapResolver;
use Zend\View\Resolver\TemplatePathStack;
use Zend\View\Strategy\FeedStrategy;
use Zend\View\Strategy\JsonStrategy;

class ServiceListenerFactoryTest extends TestCase
{

    /**
     * @var ServiceManager
     */
    private $sm;

    /**
     * @var ServiceListenerFactory
     */
    private $factory;

    public function setUp()
    {
        $this->sm = $this->getMockBuilder(ServiceManager::class)
            ->setMethods(['get'])
            ->getMock();

        $this->factory  = new ServiceListenerFactory();
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage The value of service_listener_options must be an array, string given.
     */
    public function testInvalidOptionType()
    {
        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue(['service_listener_options' => 'string']));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain service_manager key.
     */
    public function testMissingServiceManager()
    {
        $config['service_listener_options'][0]['service_manager'] = null;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, service_manager must be a string,
     *                           integer given.
     */
    public function testInvalidTypeServiceManager()
    {
        $config['service_listener_options'][0]['service_manager'] = 1;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain config_key key.
     */
    public function testMissingConfigKey()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = null;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, config_key must be a string, integer given.
     */
    public function testInvalidTypeConfigKey()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 1;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain interface key.
     */
    public function testMissingInterface()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = null;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, interface must be a string, integer given.
     */
    public function testInvalidTypeInterface()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 1;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, 0 array must contain method key.
     */
    public function testMissingMethod()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = null;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @expectedException        Zend\ServiceManager\Exception\ServiceNotCreatedException
     * @expectedExceptionMessage Invalid service listener options detected, method must be a string, integer given.
     */
    public function testInvalidTypeMethod()
    {
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 1;

        $this->sm->expects($this->once())
                 ->method('get')
                 ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    /**
     * @dataProvider fullQualifiedClassNameProvider
     */
    public function testWillProvideProperAliasesForReflectionBasedAbstractFactories($fqcn)
    {
        $configProperty = (new ReflectionProperty(ServiceListenerFactory::class, 'defaultServiceConfig'));
        $configProperty->setAccessible(true);
        $config = new Config($configProperty->getValue($this->factory));
        $services = new ServiceManager();
        $config->configureServiceManager($services);

        $this->assertTrue($services->has($fqcn), sprintf('Missing alias/factory/invokable for "%s"', $fqcn));
    }

    public function fullQualifiedClassNameProvider()
    {
        return [
            'PluginManager' => [PluginManager::class],
            'InjectTemplateListener' => [InjectTemplateListener::class],
            'RendererInterface' => [RendererInterface::class],
            'TemplateMapResolver' => [TemplateMapResolver::class],
            'TemplatePathStack' => [TemplatePathStack::class],
            'AggregateResolver' => [AggregateResolver::class],
            'ResolverInterface' => [ResolverInterface::class],
            'ControllerManager' => [ControllerManager::class],
            'DispatchListener' => [DispatchListener::class],
            'ExceptionStrategy' => [ExceptionStrategy::class],
            'HttpMethodListener' => [HttpMethodListener::class],
            'RouteNotFoundStrategy' => [RouteNotFoundStrategy::class],
            'ViewManager' => [ViewManager::class],
            'Request' => [Request::class],
            'Response' => [Response::class],
            'FeedStrategy' => [FeedStrategy::class],
            'JsonStrategy' => [JsonStrategy::class],
            'PrefixPathStackResolver' => [PrefixPathStackResolver::class],
        ];
    }
}
