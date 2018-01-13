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
use Zend\Mvc\Container\HttpMethodListenerFactory;
use Zend\Mvc\HttpMethodListener;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\HttpMethodListenerFactory
 */
class HttpMethodListenerFactoryTest extends TestCase
{
    use ContainerTrait;

    /**
     * @var ObjectProphecy
     */
    protected $container;

    /**
     * @var HttpMethodListenerFactory
     */
    private $factory;

    public function setUp()
    {
        $this->container = $this->mockContainerInterface();
        $this->factory = new HttpMethodListenerFactory();
    }

    public function testCreateWithDefaults()
    {
        $listener = $this->factory->__invoke($this->container->reveal(), HttpMethodListener::class);
        $this->assertTrue($listener->isEnabled());
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testCreateWithConfig()
    {
        $config['http_methods_listener'] = [
            'enabled' => false,
            'allowed_methods' => ['FOO', 'BAR']
        ];

        $this->injectServiceInContainer($this->container, 'config', $config);

        $listener = $this->factory->__invoke($this->container->reveal(), HttpMethodListener::class);

        $listenerConfig = $config['http_methods_listener'];

        $this->assertSame($listenerConfig['enabled'], $listener->isEnabled());
        $this->assertSame($listenerConfig['allowed_methods'], $listener->getAllowedMethods());
    }
}
