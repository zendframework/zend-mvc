<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response;
use Zend\EventManager\EventInterface as Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;

/**
 * Abstract controller
 *
 * Convenience methods for pre-built plugins (@see __call):
 * @codingStandardsIgnoreStart
 * @method \Zend\View\Model\ModelInterface acceptableViewModelSelector(array $matchAgainst = null, bool $returnDefault = true, \Zend\Http\Header\Accept\FieldValuePart\AbstractFieldValuePart $resultReference = null)
 * @codingStandardsIgnoreEnd
 * @method \Zend\Mvc\Controller\Plugin\Forward forward()
 * @method \Zend\Mvc\Controller\Plugin\Layout|\Zend\View\Model\ModelInterface layout(string $template = null)
 * @method \Zend\Mvc\Controller\Plugin\Params|mixed params(string $param = null, mixed $default = null)
 * @method \Zend\Mvc\Controller\Plugin\Redirect redirect()
 * @method \Zend\Mvc\Controller\Plugin\Url url()
 * @method \Zend\View\Model\ViewModel createHttpNotFoundModel(Response $response)
 */
abstract class AbstractController implements
    Dispatchable,
    EventManagerAwareInterface,
    InjectApplicationEventInterface
{
    /**
     * @var PluginManager
     */
    protected $plugins;

    /**
     * @var MvcEvent
     */
    protected $event;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var null|string|string[]
     */
    protected $eventIdentifier;

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    abstract public function onDispatch(MvcEvent $e);

    /**
     * Dispatch a request
     *
     * @events dispatch.pre, dispatch.post
     * @param Request $request
     * @return mixed|Response
     */
    public function dispatch(Request $request)
    {
        $e = $this->getEvent();
        $e->setName(MvcEvent::EVENT_DISPATCH);
        $e->setRequest($request);
        $e->setTarget($this);

        $result = $this->getEventManager()->triggerEventUntil(function ($test) {
            return ($test instanceof ResponseInterface);
        }, $e);

        if ($result->stopped()) {
            return $result->last();
        }

        return $e->getResult();
    }

    /**
     * Get request object
     *
     * @return null|Request
     */
    public function getRequest() : ?Request
    {
        return $this->getEvent()->getRequest();
    }

    /**
     * Get response object
     *
     * @return ResponseInterface
     */
    public function getResponse() : ?ResponseInterface
    {
        return $this->getEvent()->getResponse();
    }

    /**
     * Set the event manager instance used by this context
     *
     * @param EventManagerInterface $events
     */
    public function setEventManager(EventManagerInterface $events) : void
    {
        $className = get_class($this);

        $nsPos = strpos($className, '\\') ?: 0;
        $events->setIdentifiers(array_merge(
            [
                __CLASS__,
                $className,
                substr($className, 0, $nsPos)
            ],
            array_values(class_implements($className)),
            (array) $this->eventIdentifier
        ));

        $this->events = $events;
        $this->attachDefaultListeners();
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
        if (! $this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     * Set an event to use during dispatch
     *
     * By default, will re-cast to MvcEvent if another event type is provided.
     *
     * @param  Event $e
     * @return void
     */
    public function setEvent(Event $e)
    {
        if (! $e instanceof MvcEvent) {
            $eventParams = $e->getParams();
            $e = new MvcEvent();
            $e->setParams($eventParams);
            unset($eventParams);
        }
        $this->event = $e;
    }

    /**
     * Get the attached event
     *
     * Will create a new MvcEvent if none provided.
     *
     * @return MvcEvent
     */
    public function getEvent()
    {
        if (! $this->event) {
            $this->setEvent(new MvcEvent());
        }

        return $this->event;
    }

    /**
     * Get plugin manager
     */
    public function getPluginManager() : PluginManager
    {
        if (! $this->plugins) {
            $this->setPluginManager(new PluginManager(new ServiceManager()));
        }

        $this->plugins->setController($this);
        return $this->plugins;
    }

    /**
     * Set plugin manager
     *
     * @param PluginManager $plugins
     */
    public function setPluginManager(PluginManager $plugins) : void
    {
        $this->plugins = $plugins;
        $this->plugins->setController($this);
    }

    /**
     * Get plugin instance
     *
     * @param  string     $name    Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return mixed
     */
    public function plugin($name, array $options = null)
    {
        return $this->getPluginManager()->get($name, $options);
    }

    /**
     * Method overloading: return/call plugins
     *
     * If the plugin is a functor, call it, passing the parameters provided.
     * Otherwise, return the plugin instance.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        $plugin = $this->plugin($method);
        if (is_callable($plugin)) {
            return call_user_func_array($plugin, $params);
        }

        return $plugin;
    }

    /**
     * Register the default events for this controller
     *
     * @return void
     */
    protected function attachDefaultListeners()
    {
        $events = $this->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch']);
    }

    /**
     * Transform an "action" token into a method name
     *
     * @param  string $action
     * @return string
     */
    public static function getMethodFromAction($action)
    {
        $method  = str_replace(['.', '-', '_'], ' ', $action);
        $method  = ucwords($method);
        $method  = str_replace(' ', '', $method);
        $method  = lcfirst($method);
        $method .= 'Action';

        return $method;
    }
}
