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
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Mvc\Application;
use Zend\Mvc\Emitter\EmitterStack;

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
        if ($container->has(EmitterInterface::class)) {
            $emitter = $container->get(EmitterInterface::class);
        } else {
            $emitter = new EmitterStack();
            $emitter->push(new SapiEmitter());
        }

        $config = $container->get('config') ?? [];
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
