<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Mvc\Bootstrapper\Aggregate;
use Zend\Mvc\Bootstrapper\BootstrapEmitter;
use Zend\Mvc\Bootstrapper\DefaultListenerProvider;
use Zend\Mvc\Bootstrapper\ListenerProvider;

final class ApplicationBootstrapperFactory
{
    public function __invoke(ContainerInterface $container) : Aggregate
    {
        return new Aggregate([
            new DefaultListenerProvider($container),
            $container->get(ListenerProvider::class),
            new BootstrapEmitter(),
        ]);
    }
}
