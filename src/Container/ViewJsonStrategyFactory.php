<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Strategy\JsonStrategy;

class ViewJsonStrategyFactory
{
    /**
     * Create and return the JSON view strategy
     *
     * Retrieves the ViewJsonRenderer service from the service locator, and
     * injects it into the constructor for the JSON strategy.
     *
     * It then attaches the strategy to the View service, at a priority of 100.
     *
     * @param  ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return JsonStrategy
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null) : JsonStrategy
    {
        $jsonRenderer = $container->get(JsonRenderer::class);
        $jsonStrategy = new JsonStrategy($jsonRenderer);
        return $jsonStrategy;
    }
}
