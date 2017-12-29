<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Mvc\Controller\ControllerManager;

class ControllerManagerFactory
{
    /**
     * Create the controller manager service
     *
     * Creates and returns an instance of ControllerManager. The
     * only controllers this manager will allow are those defined in the
     * application configuration's "controllers" array. If a controller is
     * matched, the scoped manager will attempt to load the controller.
     * Finally, it will attempt to inject the controller plugin manager
     * if the controller implements a setPluginManager() method.
     *
     * @param  ContainerInterface $container
     * @return ControllerManager
     */
    public function __invoke(ContainerInterface $container) : ControllerManager
    {
        return new ControllerManager($container, $this->getControllersConfig($container));
    }

    public function getControllersConfig(ContainerInterface $container) : array
    {
        $config = $container->has('config') ? $container->get('config') : [];
        return $config['controllers'] ?? [];
    }
}
