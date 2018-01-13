<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\Mvc\Container\EventManagerFactory;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\EventManagerFactory
 */
class EventManagerFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testCreateListener()
    {
        $container = $this->mockContainerInterface();
        $factory = new EventManagerFactory();

        $events = $factory->__invoke($container->reveal(), 'EventManager');
        $this->assertInstanceOf(EventManager::class, $events);
    }

    public function testInjectsSharedEventManagerIfAvailable()
    {
        $container = $this->mockContainerInterface();
        $factory = new EventManagerFactory();
        $shared = new SharedEventManager();
        $this->injectServiceInContainer($container, 'SharedEventManager', $shared);

        $events = $factory->__invoke($container->reveal(), 'EventManager');
        $this->assertSame($shared, $events->getSharedManager());
    }
}
