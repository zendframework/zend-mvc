<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Container\ViewJsonStrategyFactory;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Strategy\JsonStrategy;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ViewJsonStrategyFactory
 */
class ViewJsonStrategyFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testReturnsJsonStrategy()
    {
        $container = $this->mockContainerInterface();
        $renderer  = $this->prophesize(JsonRenderer::class);
        $this->injectServiceInContainer($container, JsonRenderer::class, $renderer->reveal());

        $factory = new ViewJsonStrategyFactory();
        $result  = $factory($container->reveal(), JsonStrategy::class);
        $this->assertInstanceOf(JsonStrategy::class, $result);
    }
}
