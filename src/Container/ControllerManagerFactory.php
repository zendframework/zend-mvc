<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Mvc\Controller\ControllerManager;

final class ControllerManagerFactory
{
    public function __invoke(ContainerInterface $container) : ControllerManager
    {
        return new ControllerManager($container, self::getConfig($container));
    }

    public static function getConfig(ContainerInterface $container) : array
    {
        if (! $container->has('config')) {
            return [];
        }
        return $container->get('config')['controllers'] ?? [];
    }
}
