<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Zend\Mvc\Container\ViewJsonStrategyFactory;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Strategy\JsonStrategy;

class ViewJsonStrategyFactoryTest extends TestCase
{
    private function createContainer()
    {
        $renderer  = $this->prophesize(JsonRenderer::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ViewJsonRenderer')->will(function () use ($renderer) {
            return $renderer->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsJsonStrategy()
    {
        $factory = new ViewJsonStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewJsonStrategy');
        $this->assertInstanceOf(JsonStrategy::class, $result);
    }
}
