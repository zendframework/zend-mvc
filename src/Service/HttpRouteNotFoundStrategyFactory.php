<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Mvc\View\Http\RouteNotFoundStrategy;
use Zend\ServiceManager\Factory\FactoryInterface;

class HttpRouteNotFoundStrategyFactory implements FactoryInterface
{
    use HttpViewManagerConfigTrait;

    /**
     * @param  ContainerInterface $container
     * @param  string             $name
     * @param  null|array         $options
     * @return RouteNotFoundStrategy
     */
    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        $strategy = new RouteNotFoundStrategy();
        $config   = $this->getConfig($container);

        $this->injectDisplayExceptions($strategy, $config);
        $this->injectDisplayNotFoundReason($strategy, $config);
        $this->injectNotFoundTemplate($strategy, $config);

        return $strategy;
    }

    /**
     * Inject strategy with configured display_exceptions flag.
     *
     * @param RouteNotFoundStrategy $strategy
     * @param array                 $config
     */
    private function injectDisplayExceptions(RouteNotFoundStrategy $strategy, array $config)
    {
        $flag = $config['display_exceptions'] ?? false;
        $strategy->setDisplayExceptions($flag);
    }

    /**
     * Inject strategy with configured display_not_found_reason flag.
     *
     * @param RouteNotFoundStrategy $strategy
     * @param array                 $config
     */
    private function injectDisplayNotFoundReason(RouteNotFoundStrategy $strategy, array $config)
    {
        $flag = $config['display_not_found_reason'] ?? false;
        $strategy->setDisplayNotFoundReason($flag);
    }

    /**
     * Inject strategy with configured not_found_template.
     *
     * @param RouteNotFoundStrategy $strategy
     * @param array                 $config
     */
    private function injectNotFoundTemplate(RouteNotFoundStrategy $strategy, array $config)
    {
        $template = $config['not_found_template'] ?? '404';
        $strategy->setNotFoundTemplate($template);
    }
}
