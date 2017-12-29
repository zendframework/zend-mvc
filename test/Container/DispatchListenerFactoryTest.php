<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Container\DispatchListenerFactory;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\DispatchListener;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\DispatchListenerFactory
 */
class DispatchListenerFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testCreateListener()
    {
        $container = $this->mockContainerInterface();
        $this->injectServiceInContainer(
            $container,
            ControllerManager::class,
            $this->prophesize(ControllerManager::class)->reveal()
        );
        $factory = new DispatchListenerFactory();

        $listener = $factory->__invoke($container->reveal());
        $this->assertInstanceOf(DispatchListener::class, $listener);
    }
}
