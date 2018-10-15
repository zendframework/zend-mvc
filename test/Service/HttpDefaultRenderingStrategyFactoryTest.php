<?php

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Mvc\Service\HttpDefaultRenderingStrategyFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Zend\Mvc\View\Http\DefaultRenderingStrategy;
use Zend\View\View;

class HttpDefaultRenderingStrategyFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     */
    protected $serviceLocator;

    public function setUp()
    {
        $this->serviceLocator = $this->prophesize(ContainerInterface::class);
    }

    public function testInvoke()
    {
        $factory = new HttpDefaultRenderingStrategyFactory();

        $view = $this->prophesize(View::class);
        $this->serviceLocator->get(View::class)
            ->willReturn($view->reveal());

        $this->serviceLocator->has('config')->willReturn(true);
        $this->serviceLocator->get('config')
            ->willReturn([
                'view_manager' => [
                    'layout' => 'foo',
                ],
            ]);

        $instance = $factory($this->serviceLocator->reveal(), 'foo');

        $this->assertInstanceOf(DefaultRenderingStrategy::class, $instance);
        $this->assertSame('foo', $instance->getLayoutTemplate());
    }

    public function testInvokeWithArrayAccessConfig()
    {
        $factory = new HttpDefaultRenderingStrategyFactory();

        $view = $this->prophesize(View::class);
        $this->serviceLocator->get(View::class)
            ->willReturn($view->reveal());

        $this->serviceLocator->has('config')->willReturn(true);
        $this->serviceLocator->get('config')
            ->shouldBeCalled()
            ->willReturn(new \ArrayObject([
                'view_manager' => [
                    'layout' => 'foo',
                ],
            ]));

        $factory->gett();

        /** @var DefaultRenderingStrategy $instance */
        $instance = $factory($this->serviceLocator->reveal(), 'foo');

        $this->assertInstanceOf(DefaultRenderingStrategy::class, $instance);
        $this->assertSame('foo', $instance->getLayoutTemplate());
    }

    public function testInvokeWithEmptyObject()
    {
        $factory = new HttpDefaultRenderingStrategyFactory();

        $view = $this->prophesize(View::class);
        $this->serviceLocator->get(View::class)
            ->willReturn($view->reveal());

        $this->serviceLocator->has('config')->willReturn(true);
        $this->serviceLocator->get('config')
            ->willReturn([]);

        $instance = $factory($this->serviceLocator->reveal(), 'foo');

        $this->assertInstanceOf(DefaultRenderingStrategy::class, $instance);
        $this->assertSame('layout/layout', $instance->getLayoutTemplate());
    }
}
