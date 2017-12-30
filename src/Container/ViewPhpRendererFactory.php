<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\View\HelperPluginManager;
use Zend\View\Renderer\PhpRenderer;

class ViewPhpRendererFactory
{
    /**
     * @param  ContainerInterface $container
     * @return PhpRenderer
     */
    public function __invoke(ContainerInterface $container) : PhpRenderer
    {
        $renderer = new PhpRenderer();
        $renderer->setHelperPluginManager($container->get(HelperPluginManager::class));
        $renderer->setResolver($container->get('Zend\Mvc\View\Resolver'));

        return $renderer;
    }
}
