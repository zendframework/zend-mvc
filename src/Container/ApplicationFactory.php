<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Mvc\Application;

class ApplicationFactory
{
    /**
     * Create the Mvc Application
     *
     * @param  ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return Application
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null) : Application
    {
        $emitter = $container->has(EmitterInterface::class)
            ? $container->get(EmitterInterface::class)
            : null;

        $config = $container->get('config');
        $listeners = $config[Application::class]['listeners'] ?? [];

        $application = new Application(
            $container,
            $container->get('Zend\Mvc\Router'),
            $container->get('EventManager'),
            $emitter,
            $listeners
        );

        return $application;
    }
}
