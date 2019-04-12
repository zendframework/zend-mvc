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
use Zend\Mvc\Service\ViewFeedStrategyFactory;
use Zend\View\Renderer\FeedRenderer;
use Zend\View\Strategy\FeedStrategy;

class ViewFeedStrategyFactoryTest extends TestCase
{
    private function createContainer()
    {
        $renderer  = $this->prophesize(FeedRenderer::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ViewFeedRenderer')->will(function () use ($renderer) {
            return $renderer->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsFeedStrategy()
    {
        $factory = new ViewFeedStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewFeedStrategy');
        $this->assertInstanceOf(FeedStrategy::class, $result);
    }
}
