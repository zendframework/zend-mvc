<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use Psr\Container\ContainerInterface;
use Zend\Mvc\View\Http\DefaultRenderingStrategy;
use Zend\View\View;

class HttpDefaultRenderingStrategyFactory
{
    use HttpViewManagerConfigTrait;

    public function __invoke(ContainerInterface $container) : DefaultRenderingStrategy
    {
        $strategy = new DefaultRenderingStrategy($container->get(View::class));
        $config   = $this->getConfig($container);

        $this->injectLayoutTemplate($strategy, $config);

        return $strategy;
    }

    /**
     * Inject layout template.
     *
     * Uses layout template from configuration; if none available, defaults to "layout/layout".
     *
     * @param DefaultRenderingStrategy $strategy
     * @param array                    $config
     */
    private function injectLayoutTemplate(DefaultRenderingStrategy $strategy, array $config)
    {
        $layout = $config['layout'] ?? 'layout/layout';
        $strategy->setLayoutTemplate($layout);
    }
}
