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
use Zend\Mvc\Bootstrapper\BootstrapperInterface;

class ApplicationFactory
{
    public function __invoke(ContainerInterface $container) : Application
    {
        return new Application(
            $container,
            $container->get('EventManager'),
            $container->get(BootstrapperInterface::class),
            $container->get('Request'),
            $container->get('Response')
        );
    }
}
