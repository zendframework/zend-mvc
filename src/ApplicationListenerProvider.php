<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Psr\Container\ContainerInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\Exception\DomainException;
use Zend\Mvc\Exception\InvalidArgumentException;

use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Placeholder until PSR event manager implementation replaces current implementation
 */
class ApplicationListenerProvider implements ListenerAggregateInterface
{
    /** @var string[] */
    private $listeners;
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container, array $listeners)
    {
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
        $this->container = $container;
    }

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     * @param int                   $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1) : void
    {
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
            $listener->attach($events, $priority);
        }
    }

    /**
     * Detach all previously attached listeners
     *
     * @param EventManagerInterface $events
     * @return void
     */
    public function detach(EventManagerInterface $events)
    {
        // intentionally omitted
    }
}
