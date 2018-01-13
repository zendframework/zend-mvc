<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Mvc\Controller\PluginManager;

class ControllerPluginManagerFactory
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return PluginManager
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null) : PluginManager
    {
        if (null !== $options) {
            return new PluginManager($container, $options);
        }
        return new PluginManager($container, $this->getPluginsConfig($container));
    }

    public function getPluginsConfig(ContainerInterface $container) : array
    {
        $config = $container->has('config') ? $container->get('config') : [];
        return $config['controller_plugins'] ?? [];
    }
}
