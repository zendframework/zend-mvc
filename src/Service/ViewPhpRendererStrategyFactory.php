<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use Psr\Container\ContainerInterface;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Strategy\PhpRendererStrategy;

class ViewPhpRendererStrategyFactory
{
    public function __invoke(ContainerInterface $container) : PhpRendererStrategy
    {
        return new PhpRendererStrategy($container->get(PhpRenderer::class));
    }
}
