<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Container\ViewFeedStrategyFactory;
use Zend\View\Renderer\FeedRenderer;
use Zend\View\Strategy\FeedStrategy;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ViewFeedStrategyFactory
 */
class ViewFeedStrategyFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testReturnsFeedStrategy()
    {
        $container = $this->mockContainerInterface();
        $renderer  = $this->prophesize(FeedRenderer::class);
        $this->injectServiceInContainer($container, FeedRenderer::class, $renderer->reveal());

        $factory = new ViewFeedStrategyFactory();

        $result  = $factory($container->reveal(), 'ViewFeedStrategy');
        $this->assertInstanceOf(FeedStrategy::class, $result);
    }
}
