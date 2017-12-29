<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Mvc\HttpMethodListener;

class HttpMethodListenerFactory
{
    public function __invoke(ContainerInterface $container) : HttpMethodListener
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $enabled = $config['http_methods_listener']['enabled'] ?? true;
        $allowedMethods = $config['http_methods_listener']['allowed_methods'] ?? null;

        return new HttpMethodListener($enabled, $allowedMethods);
    }
}
