<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\Service\ServiceListenerFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\ServiceManager;

class ServiceListenerFactoryTest extends TestCase
{
    /** @var ServiceManager|MockObject */
    private $sm;
    /** @var ServiceListenerFactory */
    private $factory;

    public function setUp() : void
    {
        $this->sm = $this->getMockBuilder(ServiceManager::class)
            ->setMethods(['get'])
            ->getMock();

        $this->factory = new ServiceListenerFactory();
    }

    public function testInvalidOptionType()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('The value of service_listener_options must be an array, string given.');
        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue(['service_listener_options' => 'string']));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingServiceManager()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain service_manager key.'
        );
        $config['service_listener_options'][0]['service_manager'] = null;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeServiceManager()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, service_manager must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 1;
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingConfigKey()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain config_key key.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = null;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeConfigKey()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, config_key must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 1;
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingInterface()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain interface key.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = null;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeInterface()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, interface must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 1;
        $config['service_listener_options'][0]['method']          = 'test';

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testMissingMethod()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, 0 array must contain method key.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = null;

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }

    public function testInvalidTypeMethod()
    {
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage(
            'Invalid service listener options detected, method must be a string, integer given.'
        );
        $config['service_listener_options'][0]['service_manager'] = 'test';
        $config['service_listener_options'][0]['config_key']      = 'test';
        $config['service_listener_options'][0]['interface']       = 'test';
        $config['service_listener_options'][0]['method']          = 1;

        $this->sm->expects($this->once())
            ->method('get')
            ->will($this->returnValue($config));

        $this->factory->__invoke($this->sm, 'ServiceListener');
    }
}
