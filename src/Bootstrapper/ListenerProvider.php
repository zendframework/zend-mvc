<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Bootstrapper;

use Psr\Container\ContainerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\Exception\DomainException;
use Zend\Mvc\Exception\InvalidArgumentException;

use function get_class;
use function getType;
use function is_object;
use function is_string;
use function sprintf;

class ListenerProvider implements BootstrapperInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var ListenerAggregateInterface[]|string[] */
    private $listeners;

    public function __construct(ContainerInterface $container, array $listeners)
    {
        $this->container = $container;
        foreach ($listeners as $listener) {
            if (is_string($listener) || $listener instanceof ListenerAggregateInterface) {
                continue;
            }
            throw new InvalidArgumentException(sprintf(
                'String service key or instance of %s expected, got %s',
                ListenerAggregateInterface::class,
                is_object($listener) ? get_class($listener) : gettype($listener)
            ));
        }
        $this->listeners = $listeners;
    }

    public function bootstrap(ApplicationInterface $application) : void
    {
        $events = $application->getEventManager();

        foreach ($this->listeners as $listener) {
            if (is_string($listener)) {
                $key      = $listener;
                $listener = $this->container->get($listener);
                if (! $listener instanceof ListenerAggregateInterface) {
                    throw new DomainException(sprintf(
                        'Service with key %s is expected to be instance of %s, got %s',
                        $key,
                        ListenerAggregateInterface::class,
                        is_object($listener) ? get_class($listener) : gettype($listener)
                    ));
                }
            }
            $listener->attach($events);
        }
    }
}
