<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Mvc\AssetsListener;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class AssetsListenerFactory implements FactoryInterface
{
    /**
     * Create the default dispatch listener.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return DispatchListener
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $listener = new AssetsListener();
        $listener->setAssetsManager($container->get('AssetsManager'));
        $listener->setAssetsResolver($container->get('ViewAssetsResolver'));
        $listener->setFilterManager($container->get('FilterManager'));

        $config = $container->get('config');

        if (isset($config['assets_manager']['router_name'])) {
            $listener->setRouteName($config['assets_manager']['router_name']);
        }
        if (isset($config['assets_manager']['router_cache_folder'])) {
            $listener->setRouterCacheFolder($config['assets_manager']['router_cache_folder']);
        }
        if (isset($config['assets_manager']['use_internal_router'])) {
            $listener->setUseInternalRouter($config['assets_manager']['use_internal_router']);
        }
        if (isset($config['assets_manager']['router'])) {
            $listener->setRouter($config['assets_manager']['router']);
        }
        if (isset($config['assets_manager']['cache_to_public'])) {
            $listener->setCacheToPublic($config['assets_manager']['cache_to_public']);
        }
        return $listener;
    }

    /**
     * Create and return DispatchListener instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DispatchListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, AssetsListener::class);
    }
}
