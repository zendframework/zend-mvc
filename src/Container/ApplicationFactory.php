<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Interop\Container\ContainerInterface;
use Zend\Mvc\Application;
use Zend\Mvc\Exception\RuntimeException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\ServiceManager;

class ApplicationFactory implements FactoryInterface
{
    /**
     * Create the Application service
     *
     * Creates a Zend\Mvc\Application service, passing it the configuration
     * service and the service manager instance.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return Application
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        if (! $container instanceof ServiceManager) {
            // @TODO convert Application to use ContainerInterface
            throw new RuntimeException('Mvc Application requires ServiceManager as ContainerInterface implementation');
        }
        return new Application(
            $container,
            $container->get('EventManager'),
            $container->get('Request'),
            $container->get('Response')
        );
    }
}
