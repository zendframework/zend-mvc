<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Psr\Container\ContainerInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;

/**
 * Main application class for invoking applications
 *
 * Expects the user will provide a configured ServiceManager, configured with
 * the following services:
 *
 * - EventManager
 * - Request
 * - Response
 * - RouteListener
 * - Router
 * - DispatchListener
 * - MiddlewareListener
 * - ViewManager
 *
 * The most common workflow is:
 * <code>
 * $services = new Zend\ServiceManager\ServiceManager($servicesConfig);
 * $app      = new Application($appConfig, $services);
 * $app->bootstrap();
 * $response = $app->run();
 * $response->send();
 * </code>
 *
 * bootstrap() opts in to the default route, dispatch, and view listeners,
 * sets up the MvcEvent, and triggers the bootstrap event. This can be omitted
 * if you wish to setup your own listeners and/or workflow; alternately, you
 * can simply extend the class to override such behavior.
 */
class Application implements ApplicationInterface
{
    public const ERROR_CONTROLLER_CANNOT_DISPATCH = 'error-controller-cannot-dispatch';
    public const ERROR_CONTROLLER_NOT_FOUND       = 'error-controller-not-found';
    public const ERROR_CONTROLLER_INVALID         = 'error-controller-invalid';
    public const ERROR_EXCEPTION                  = 'error-exception';
    public const ERROR_ROUTER_NO_MATCH            = 'error-router-no-match';
    public const ERROR_MIDDLEWARE_CANNOT_DISPATCH = 'error-middleware-cannot-dispatch';

    /**
     * MVC event token
     * @var MvcEvent
     */
    protected $event;

    /** @var EventManagerInterface */
    protected $events;

    /** @var RequestInterface */
    protected $request;

    /** @var ResponseInterface */
    protected $response;

    /** @var ContainerInterface */
    private $container;

    /**
     * Whether the application was already bootstrapped
     */
    private $bootstrapped = false;

    public function __construct(
        ContainerInterface $container,
        EventManagerInterface $events,
        ?RequestInterface $request = null,
        ?ResponseInterface $response = null
    ) {
        $this->setEventManager($events);
        $this->container = $container;
        $this->request   = $request ?: $container->get('Request');
        $this->response  = $response ?: $container->get('Response');

        $event = new MvcEvent();
        $event->setApplication($this);
        $event->setRequest($this->request);
        $event->setResponse($this->response);
        $event->setRouter($container->get('Router'));
        $this->event = $event;
    }

    /**
     * Bootstrap the application
     *
     * After calling this method application must be fully setup and ready.
     * Idempotent. Calling it multiple times have no effect.
     */
    public function bootstrap() : void
    {
        if ($this->bootstrapped) {
            return;
        }
        $this->bootstrapped = true;

        $events = $this->getEventManager();

        $mvcEvent = $this->getMvcEvent();
        $mvcEvent->setTarget($this);
        $mvcEvent->setName(MvcEvent::EVENT_BOOTSTRAP);

        // reset propagation flag if set
        $mvcEvent->stopPropagation(true);

        // Trigger bootstrap event
        $events->triggerEvent($mvcEvent);
    }

    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the request object
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response object
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the MVC event instance
     */
    public function getMvcEvent() : MvcEvent
    {
        return $this->event;
    }

    private function setEventManager(EventManagerInterface $eventManager) : void
    {
        $eventManager->setIdentifiers([
            self::class,
            static::class,
        ]);
        $this->events = $eventManager;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        return $this->events;
    }

    /**
     * Run the application
     *
     * @triggers route(MvcEvent)
     *           Routes the request, and sets the RouteMatch object in the event.
     * @triggers dispatch(MvcEvent)
     *           Dispatches a request, using the discovered RouteMatch and
     *           provided request.
     * @triggers dispatch.error(MvcEvent)
     *           On errors (controller not found, action not supported, etc.),
     *           populates the event with information about the error type,
     *           discovered controller, and controller class (if known).
     *           Typically, a handler should return a populated Response object
     *           that can be returned immediately.
     * @return self
     */
    public function run()
    {
        $this->bootstrap();

        $events = $this->events;
        $event  = $this->event;

        // Define callback used to determine whether or not to short-circuit
        $shortCircuit = function ($r) use ($event) {
            if ($r instanceof ResponseInterface) {
                return true;
            }
            if ($event->getError()) {
                return true;
            }
            return false;
        };

        // Trigger route event
        $event->setName(MvcEvent::EVENT_ROUTE);
        $event->stopPropagation(false); // Clear before triggering
        $result = $events->triggerEventUntil($shortCircuit, $event);
        if ($result->stopped()) {
            $response = $result->last();
            if ($response instanceof ResponseInterface) {
                $event->setName(MvcEvent::EVENT_FINISH);
                $event->setTarget($this);
                $event->setResponse($response);
                $event->stopPropagation(false); // Clear before triggering
                $events->triggerEvent($event);
                $this->response = $response;
                return $this;
            }
        }

        if ($event->getError()) {
            return $this->completeRequest($event);
        }

        // Trigger dispatch event
        $event->setName(MvcEvent::EVENT_DISPATCH);
        $event->stopPropagation(false); // Clear before triggering
        $result = $events->triggerEventUntil($shortCircuit, $event);

        // Complete response
        $response = $result->last();
        if ($response instanceof ResponseInterface) {
            $event->setName(MvcEvent::EVENT_FINISH);
            $event->setTarget($this);
            $event->setResponse($response);
            $event->stopPropagation(false); // Clear before triggering
            $events->triggerEvent($event);
            $this->response = $response;
            return $this;
        }

        $response = $this->response;
        $event->setResponse($response);
        return $this->completeRequest($event);
    }

    /**
     * Complete the request
     *
     * Triggers "render" and "finish" events, and returns response from
     * event object.
     *
     * @param  MvcEvent $event
     * @return Application
     */
    protected function completeRequest(MvcEvent $event)
    {
        $events = $this->events;
        $event->setTarget($this);

        $event->setName(MvcEvent::EVENT_RENDER);
        $event->stopPropagation(false); // Clear before triggering
        $events->triggerEvent($event);

        $event->setName(MvcEvent::EVENT_FINISH);
        $event->stopPropagation(false); // Clear before triggering
        $events->triggerEvent($event);

        return $this;
    }
}
