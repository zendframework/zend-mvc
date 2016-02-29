<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use ArrayObject;
use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Service\InjectTemplateListenerFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

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

        $this->assertEquals('some/module', $listener->mapController("SomeModule"));
    }

    public function testFactoryCanSetControllerMapViaArrayAccessVM()
    {
        $listener = $this->buildInjectTemplateListenerWithConfig([
            'view_manager' => new ArrayObject([
                'controller_map' => [
                    // must be an array due to type hinting on setControllerMap()
                    'SomeModule' => 'some/module',
                ],
            ])
        ]);

        $this->assertEquals('some/module', $listener->mapController("SomeModule"));
    }

    /**
     * @param mixed $config
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\Zend\Mvc\View\Http\InjectTemplateListener
     */
    private function buildInjectTemplateListenerWithConfig($config)
    {
        $serviceLocator = $this->prophesize(ServiceLocatorInterface::class);
        $serviceLocator->willImplement(ContainerInterface::class);

        $serviceLocator->get('config')->willReturn($config);

        $factory  = new InjectTemplateListenerFactory();
        $listener = $factory($serviceLocator->reveal(), 'InjectTemplateListener');

        $this->assertInstanceOf('Zend\Mvc\View\Http\InjectTemplateListener', $listener);

        return $listener;
    }
}
