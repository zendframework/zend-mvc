<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use ArrayAccess;
use Interop\Container\ContainerInterface;

trait HttpViewManagerConfigTrait
{
    /**
     * Retrieve view_manager configuration, if present.
     *
     * @param ContainerInterface $container
     * @return array|ArrayAccess
     */
    private function getConfig(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! isset($config['view_manager'])) {
            return [];
        }

        if (\is_array($config['view_manager']) || $config['view_manager'] instanceof ArrayAccess) {
            return $config['view_manager'];
        }

        return [];
    }
}
