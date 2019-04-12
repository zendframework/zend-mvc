<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use ArrayAccess;
use Psr\Container\ContainerInterface;

use function is_array;

trait HttpViewManagerConfigTrait
{
    /**
     * Retrieve view_manager configuration, if present.
     *
     * @param ContainerInterface $container
     * @return array
     */
    private function getConfig(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (isset($config['view_manager'])
            && (is_array($config['view_manager'])
                || $config['view_manager'] instanceof ArrayAccess)
        ) {
            return $config['view_manager'];
        }

        return [];
    }
}
