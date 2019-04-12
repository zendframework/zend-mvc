<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Mvc\Service\ViewManagerFactory;
use Zend\Mvc\View\Http\ViewManager as HttpViewManager;

class ViewManagerFactoryTest extends TestCase
{
    private function createContainer()
    {
        $http      = $this->prophesize(HttpViewManager::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('HttpViewManager')->will(function () use ($http) {
            return $http->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsHttpViewManager()
    {
        $factory = new ViewManagerFactory();
        $result  = $factory($this->createContainer(), 'ViewManager');
        $this->assertInstanceOf(HttpViewManager::class, $result);
    }
}
