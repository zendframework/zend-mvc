<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Service;

use ArrayObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Mvc\Service\InjectTemplateListenerFactory;
use Zend\Mvc\View\Http\InjectTemplateListener;

/**
 * Tests for {@see \Zend\Mvc\Service\InjectTemplateListenerFactory}
 *
 * @covers \Zend\Mvc\Service\InjectTemplateListenerFactory
 */
class InjectTemplateListenerFactoryTest extends TestCase
{
    public function testFactoryCanCreateInjectTemplateListener()
    {
        $this->buildInjectTemplateListenerWithConfig([]);
    }

    public function testFactoryCanSetControllerMap()
    {
        $listener = $this->buildInjectTemplateListenerWithConfig([
            'view_manager' => [
                'controller_map' => [
                    'SomeModule' => 'some/module',
                ],
            ],
        ]);

        $this->assertEquals('some/module', $listener->mapController('SomeModule'));
    }

    public function testFactoryCanSetControllerMapViaArrayAccessVM()
    {
        $listener = $this->buildInjectTemplateListenerWithConfig([
            'view_manager' => new ArrayObject([
                'controller_map' => [
                    // must be an array due to type hinting on setControllerMap()
                    'SomeModule' => 'some/module',
                ],
            ]),
        ]);

        $this->assertEquals('some/module', $listener->mapController('SomeModule'));
    }

    /**
     * @param mixed $config
     *
     * @return MockObject|InjectTemplateListener
     */
    private function buildInjectTemplateListenerWithConfig($config)
    {
        $serviceLocator = $this->prophesize(ContainerInterface::class);

        $serviceLocator->get('config')->willReturn($config);

        $factory  = new InjectTemplateListenerFactory();
        $listener = $factory($serviceLocator->reveal(), 'InjectTemplateListener');

        $this->assertInstanceOf(InjectTemplateListener::class, $listener);

        return $listener;
    }
}
