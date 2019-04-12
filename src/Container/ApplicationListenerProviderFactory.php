<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Mvc\Application;
use Zend\Mvc\ApplicationListenerProvider;

use function array_merge;

final class ApplicationListenerProviderFactory
{
    /** @var string[] */
    private static $defaultApplicationListeners = [
        'RouteListener',
        'MiddlewareListener',
        'DispatchListener',
        'HttpMethodListener',
        'ViewManager',
        'SendResponseListener',
    ];

    public function __invoke(ContainerInterface $container) : ApplicationListenerProvider
    {
        $listeners = array_merge(
            self::getDefaultListeners(),
            self::getListenersConfig($container)
        );
        return new ApplicationListenerProvider($container, $listeners);
    }

    public static function getListenersConfig(ContainerInterface $container) : array
    {
        if (! $container->has('config')) {
            return [];
        }

        return $container->get('config')[Application::class]['listeners'] ?? [];
    }

    /**
     * @return string[] List of container ids for application default listeners
     */
    public static function getDefaultListeners() : array
    {
        return self::$defaultApplicationListeners;
    }
}
