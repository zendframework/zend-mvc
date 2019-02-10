<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Bootstrapper;

use Psr\Container\ContainerInterface;
use Zend\Mvc\ApplicationInterface;

class DefaultListenerProvider implements BootstrapperInterface
{
    /**
     * Default application event listeners
     *
     * @var string[]
     */
    private $defaultListeners = [
        'RouteListener',
        'MiddlewareListener',
        'DispatchListener',
        'HttpMethodListener',
        'ViewManager',
        'SendResponseListener',
    ];

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function bootstrap(ApplicationInterface $application) : void
    {
        $events = $application->getEventManager();

        foreach ($this->defaultListeners as $listenerKey) {
            $listenerAggregate = $this->container->get($listenerKey);
            $listenerAggregate->attach($events);
        }
    }
}
