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
use Zend\Mvc\DispatchListener;

class DispatchListenerFactory
{
    /**
     * Create the default dispatch listener.
     *
     * @param  ContainerInterface $container
     * @return DispatchListener
     */
    public function __invoke(ContainerInterface $container) : DispatchListener
    {
        return new DispatchListener($container->get(ControllerManager::class));
    }
}
