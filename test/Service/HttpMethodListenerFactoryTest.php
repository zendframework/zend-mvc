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
use Psr\Container\ContainerInterface;
use Zend\Mvc\Service\HttpMethodListenerFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @covers \Zend\Mvc\Service\HttpMethodListenerFactory
 */
class HttpMethodListenerFactoryTest extends TestCase
{
    /** @var ServiceLocatorInterface|MockObject */
    protected $serviceLocator;

    public function setUp() : void
    {
        $this->serviceLocator = $this->prophesize(ContainerInterface::class);
    }

    public function testCreateWithDefaults()
    {
        $factory  = new HttpMethodListenerFactory();
        $listener = $factory($this->serviceLocator->reveal(), 'HttpMethodListener');
        $this->assertTrue($listener->isEnabled());
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testCreateWithConfig()
    {
        $config['http_methods_listener'] = [
            'enabled'         => false,
            'allowed_methods' => ['FOO', 'BAR'],
        ];

        $this->serviceLocator->get('config')->willReturn($config);

        $factory  = new HttpMethodListenerFactory();
        $listener = $factory($this->serviceLocator->reveal(), 'HttpMethodListener');

        $listenerConfig = $config['http_methods_listener'];

        $this->assertSame($listenerConfig['enabled'], $listener->isEnabled());
        $this->assertSame($listenerConfig['allowed_methods'], $listener->getAllowedMethods());
    }
}
