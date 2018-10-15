<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\View\Http;

use ArrayAccess;
use Traversable;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\ViewModel;
use Zend\View\View;
use Zend\Mvc\Exception;
use Zend\Stdlib\DispatchableInterface;

/**
 * Prepares the view layer
 *
 * Instantiates and configures all classes related to the view layer, including
 * the renderer (and its associated resolver(s) and helper manager), the view
 * object (and its associated rendering strategies), and the various MVC
 * strategies and listeners.
 *
 * Defines and manages the following services:
 *
 * - ViewHelperManager (also aliased to Zend\View\HelperPluginManager)
 * - ViewTemplateMapResolver (also aliased to Zend\View\Resolver\TemplateMapResolver)
 * - ViewTemplatePathStack (also aliased to Zend\View\Resolver\TemplatePathStack)
 * - ViewResolver (also aliased to Zend\View\Resolver\AggregateResolver and ResolverInterface)
 * - ViewRenderer (also aliased to Zend\View\Renderer\PhpRenderer and RendererInterface)
 * - ViewPhpRendererStrategy (also aliased to Zend\View\Strategy\PhpRendererStrategy)
 * - View (also aliased to Zend\View\View)
 * - DefaultRenderingStrategy (also aliased to Zend\Mvc\View\Http\DefaultRenderingStrategy)
 * - ExceptionStrategy (also aliased to Zend\Mvc\View\Http\ExceptionStrategy)
 * - RouteNotFoundStrategy (also aliased to Zend\Mvc\View\Http\RouteNotFoundStrategy and 404Strategy)
 * - ViewModel
 */
class ViewManager extends AbstractListenerAggregate
{
    /**
     * @var array|ArrayAccess application configuration service
     */
    protected $config;

    /**
     * @var MvcEvent
     */
    protected $event;

    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**@+
     * Various properties representing strategies and objects instantiated and
     * configured by the view manager
     */
    protected $helperManager;
    protected $mvcRenderingStrategy;
    protected $renderer;
    protected $rendererStrategy;
    protected $resolver;
    protected $view;
    protected $viewModel;
    /**@-*/

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, [$this, 'onBootstrap'], 10000);
    }

    /**
     * Prepares the view layer
     *
     * @param MvcEvent $event
     * @return void
     */
    public function onBootstrap($event)
    {
        $application  = $event->getApplication();

        if (null === $application) {
            throw new Exception\UnexpectedValueException('Unable to get Application from MvcEvent');
        }

        $services     = $application->getServiceManager();
        $config       = $services->get('config');
        $events       = $application->getEventManager();
        $sharedEvents = $events->getSharedManager();

        $this->config   = isset($config['view_manager'])
            && (is_array($config['view_manager'])
            || $config['view_manager'] instanceof ArrayAccess)
                ? $config['view_manager']
                : [];
        $this->services = $services;
        $this->event    = $event;

        /** @var RouteNotFoundStrategy $routeNotFoundStrategy */
        $routeNotFoundStrategy   = $services->get('HttpRouteNotFoundStrategy');
        /** @var ExceptionStrategy $exceptionStrategy */
        $exceptionStrategy       = $services->get('HttpExceptionStrategy');
        /** @var DefaultRenderingStrategy $mvcRenderingStrategy */
        $mvcRenderingStrategy    = $services->get('HttpDefaultRenderingStrategy');

        $this->injectViewModelIntoPlugin();

        /** @var InjectTemplateListener $injectTemplateListener */
        $injectTemplateListener  = $services->get(InjectTemplateListener::class);
        $createViewModelListener = new CreateViewModelListener();
        $injectViewModelListener = new InjectViewModelListener();

        $this->registerMvcRenderingStrategies($events);
        $this->registerViewStrategies();

        $routeNotFoundStrategy->attach($events);
        $exceptionStrategy->attach($events);
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$injectViewModelListener, 'injectViewModel'], -100);
        $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$injectViewModelListener, 'injectViewModel'], -100);
        $mvcRenderingStrategy->attach($events);

        if (null === $sharedEvents) {
            throw new Exception\RuntimeException('No SharedEventManager in application EventManager');
        }

        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$createViewModelListener, 'createViewModelFromArray'],
            -80
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$routeNotFoundStrategy, 'prepareNotFoundViewModel'],
            -90
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$createViewModelListener, 'createViewModelFromNull'],
            -80
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$injectTemplateListener, 'injectTemplate'],
            -90
        );
        $sharedEvents->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            [$injectViewModelListener, 'injectViewModel'],
            -100
        );
    }

    /**
     * Retrieves the View instance
     *
     * @return View
     */
    public function getView()
    {
        if ($this->view) {
            return $this->view;
        }

        $this->view = $this->services->get(View::class);
        return $this->view;
    }

    /**
     * Configures the MvcEvent view model to ensure it has the template injected
     *
     * @return \Zend\View\Model\ModelInterface
     */
    public function getViewModel()
    {
        if ($this->viewModel) {
            return $this->viewModel;
        }

        $this->viewModel = $model = $this->event->getViewModel();

        if (null === $model) {
            throw new Exception\UnexpectedValueException('No ViewModel in event');
        }

        /** @var DefaultRenderingStrategy $renderingStrategy */
        $renderingStrategy = $this->services->get('HttpDefaultRenderingStrategy');
        $layoutTemplate  = $renderingStrategy->getLayoutTemplate();
        $model->setTemplate($layoutTemplate);

        return $model;
    }

    /**
     * Register additional mvc rendering strategies
     *
     * If there is a "mvc_strategies" key of the view manager configuration, loop
     * through it. Pull each as a service from the service manager, and, if it
     * is a ListenerAggregate, attach it to the view, at priority 100. This
     * latter allows each to trigger before the default mvc rendering strategy,
     * and for them to trigger in the order they are registered.
     *
     * @param EventManagerInterface $events
     * @return void
     */
    protected function registerMvcRenderingStrategies(EventManagerInterface $events)
    {
        if (! isset($this->config['mvc_strategies'])) {
            return;
        }
        $mvcStrategies = $this->config['mvc_strategies'];
        if (is_string($mvcStrategies)) {
            $mvcStrategies = [$mvcStrategies];
        }
        if (! is_array($mvcStrategies) && ! $mvcStrategies instanceof Traversable) {
            return;
        }

        foreach ($mvcStrategies as $mvcStrategy) {
            if (! is_string($mvcStrategy)) {
                continue;
            }

            $listener = $this->services->get($mvcStrategy);
            if ($listener instanceof ListenerAggregateInterface) {
                $listener->attach($events, 100);
            }
        }
    }

    /**
     * Register additional view strategies
     *
     * If there is a "strategies" key of the view manager configuration, loop
     * through it. Pull each as a service from the service manager, and, if it
     * is a ListenerAggregate, attach it to the view, at priority 100. This
     * latter allows each to trigger before the default strategy, and for them
     * to trigger in the order they are registered.
     *
     * @return void
     */
    protected function registerViewStrategies()
    {
        if (! isset($this->config['strategies'])) {
            return;
        }
        $strategies = $this->config['strategies'];
        if (is_string($strategies)) {
            $strategies = [$strategies];
        }
        if (! is_array($strategies) && ! $strategies instanceof Traversable) {
            return;
        }

        $view   = $this->getView();
        $events = $view->getEventManager();

        foreach ($strategies as $strategy) {
            if (! is_string($strategy)) {
                continue;
            }

            $listener = $this->services->get($strategy);
            if ($listener instanceof ListenerAggregateInterface) {
                $listener->attach($events, 100);
            }
        }
    }

    /**
     * Injects the ViewModel view helper with the root view model.
     */
    private function injectViewModelIntoPlugin()
    {
        $model   = $this->getViewModel();
        $plugins = $this->services->get('ViewHelperManager');
        /** @var ViewModel $plugin */
        $plugin  = $plugins->get('viewmodel');
        $plugin->setRoot($model);
    }
}
