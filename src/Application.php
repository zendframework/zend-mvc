<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\Emitter\EmitterStack;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Router\RouteStackInterface;

/**
 * Main application class for invoking applications
 *
 * Expects the user will provide a configured ServiceManager, configured with
 * the following services:
 *
 * - EventManager
 * - ModuleManager
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
class Application implements
    ApplicationInterface,
    EventManagerAwareInterface
{
    const ERROR_CONTROLLER_CANNOT_DISPATCH = 'error-controller-cannot-dispatch';
    const ERROR_CONTROLLER_NOT_FOUND       = 'error-controller-not-found';
    const ERROR_CONTROLLER_INVALID         = 'error-controller-invalid';
    const ERROR_EXCEPTION                  = 'error-exception';
    const ERROR_ROUTER_NO_MATCH            = 'error-router-no-match';
    const ERROR_MIDDLEWARE_CANNOT_DISPATCH = 'error-middleware-cannot-dispatch';

    /**
     * Default application event listeners
     *
     * @var array
     */
    protected $defaultListeners = [
        RouteListener::class,
        MiddlewareListener::class,
        DispatchListener::class,
        HttpMethodListener::class,
        ViewManager::class,
    ];

    /**
     * @var string[]|ListenerAggregateInterface[]
     */
    private $listeners = [];

    /**
     * MVC event token
     * @var MvcEvent
     */
    protected $event;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EmitterInterface
     */
    private $emitter;

    /**
     * @var RouteStackInterface
     */
    private $router;

    /**
     * If application was bootstrapped
     *
     * @var bool
     */
    private $bootstrapped = false;

    /**
     * Constructor
     *
     * @param ContainerInterface $container IoC container from which to pull services
     * @param RouteStackInterface $router Configured router for RouteListener
     * @param EventManagerInterface|null $events
     * @param EmitterInterface|null $emitter Response emitter to use when `run()`
     *     is invoked
     * @param array $listeners Extra listeners to attach on bootstrap
     *     Can be container keys or instances of ListenerAggregateInterface
     */
    public function __construct(
        ContainerInterface $container,
        RouteStackInterface $router,
        EventManagerInterface $events = null,
        EmitterInterface $emitter = null,
        array $listeners = []
    ) {
        $this->container = $container;
        $this->router = $router;
        $this->setEventManager($events ?? new EventManager());
        $this->emitter = $emitter;
        $this->listeners = $listeners;

        // @TODO response prototype?
    }

    /**
     * Retrieve the application configuration
     *
     * @return array|object
     */
    public function getConfig()
    {
        return $this->container->get('config');
    }

    /**
     * Bootstrap the application
     *
     * Defines and binds the MvcEvent, and passes it the request, response, and
     * router. Attaches default listeners. Triggers the bootstrap
     * event.
     */
    public function bootstrap() : void
    {
        if ($this->bootstrapped) {
            return;
        }
        $events = $this->events;

        // @TODO may be move this to constructor
        $listeners = array_unique(array_merge($this->defaultListeners, $this->listeners), \SORT_REGULAR);
        foreach ($listeners as $listener) {
            if ($listener instanceof ListenerAggregateInterface) {
                $listener->attach($events);
                continue;
            }
            $this->container->get($listener)->attach($events);
        }

        // Setup MVC Event
        $this->event = $event = new MvcEvent();
        $event->setName(MvcEvent::EVENT_BOOTSTRAP);
        $event->setTarget($this);
        $event->setApplication($this);
        $event->setRouter($this->router);

        // Trigger bootstrap events
        $events->triggerEvent($event);

        $this->bootstrapped = true;
    }

    /**
     * Retrieve the service manager
     *
     * @return ContainerInterface
     */
    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the MVC event instance
     *
     * @return MvcEvent
     */
    public function getMvcEvent() : ?MvcEvent
    {
        return $this->event;
    }

    /**
     * Set the event manager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return void
     */
    public function setEventManager(EventManagerInterface $eventManager) : void
    {
        $eventManager->setIdentifiers([
            __CLASS__,
            get_class($this),
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
    public function getEventManager() : EventManagerInterface
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
     * @param Request|null $request
     * @return void
     */
    public function run(Request $request = null) : void
    {
        try {
            $request = $request ?: ServerRequestFactory::fromGlobals();
        } catch (InvalidArgumentException | UnexpectedValueException $e) {
            // emit bad request
            throw new \Exception('Not implemented');
        }

        $response = $this->handle($request);

        $emitter = $this->getEmitter();
        $emitter->emit($response);
    }

    public function handle(Request $request) : ResponseInterface
    {
        if (! $this->bootstrapped) {
            $this->bootstrap();
        }
        $events = $this->events;
        // @TODO revisit later for multi-request improvements
        $event  = $this->event;
        $event->setRequest($request);

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
                return $event->getResponse();
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
            return $event->getResponse();
        }

        return $this->completeRequest($event);
    }

    public function getEmitter() : EmitterInterface
    {
        if (! $this->emitter) {
            $this->emitter = new EmitterStack();
            $this->emitter->push(new SapiEmitter());
        }
        return $this->emitter;
    }

    /**
     * Complete the request
     *
     * Triggers "render" and "finish" events, and returns response from
     * event object.
     *
     * @param  MvcEvent $event
     * @return MvcEvent
     */
    protected function completeRequest(MvcEvent $event) : ResponseInterface
    {
        $events = $this->events;
        $event->setTarget($this);

        $event->setName(MvcEvent::EVENT_RENDER);
        $event->stopPropagation(false); // Clear before triggering
        $events->triggerEvent($event);

        $event->setName(MvcEvent::EVENT_FINISH);
        $event->stopPropagation(false); // Clear before triggering
        $events->triggerEvent($event);

        // @TODO handle missing response. Investigate possibility for using middleware delegate
        return ($event->getResponse() ?? new Response());
    }
}
