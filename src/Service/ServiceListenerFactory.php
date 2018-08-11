<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\ModuleManager\Listener\ServiceListener;
use Zend\ModuleManager\Listener\ServiceListenerInterface;
use Zend\Mvc\View;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\Mvc\Controller\ControllerManager;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

class ServiceListenerFactory implements FactoryInterface
{
    /**
     * @var string
     */
    const MISSING_KEY_ERROR = 'Invalid service listener options detected, %s array must contain %s key.';

    /**
     * @var string
     */
    const VALUE_TYPE_ERROR = 'Invalid service listener options detected, %s must be a string, %s given.';

    /**
     * Default mvc-related service configuration -- can be overridden by modules.
     *
     * @var array
     */
    protected $defaultServiceConfig = [
        'aliases' => [
            'application'                                => \Zend\Mvc\Application::class,
            'Application'                                => \Zend\Mvc\Application::class,
            'Config'                                     => 'config',
            'configuration'                              => 'config',
            'Configuration'                              => 'config',
            'ControllerManager'                          => ControllerManager::class,
            'ControllerPluginManager'                    => ControllerPluginManager::class,
            'DispatchListener'                           => \Zend\Mvc\DispatchListener::class,
            'HttpDefaultRenderingStrategy'               => View\Http\DefaultRenderingStrategy::class,
            'HttpExceptionStrategy'                      => View\Http\ExceptionStrategy::class,
            'HttpMethodListener'                         => \Zend\Mvc\HttpMethodListener::class,
            'HttpRouteNotFoundStrategy'                  => View\Http\RouteNotFoundStrategy::class,
            'HttpViewManager'                            => View\Http\ViewManager::class,
            'InjectTemplateListener'                     => View\Http\InjectTemplateListener::class,
            'MiddlewareListener'                         => \Zend\Mvc\MiddlewareListener::class,
            'request'                                    => HttpRequest::class,
            'Request'                                    => HttpRequest::class,
            'response'                                   => HttpResponse::class,
            'Response'                                   => HttpResponse::class,
            'RouteListener'                              => \Zend\Mvc\RouteListener::class,
            'SendResponseListener'                       => \Zend\Mvc\SendResponseListener::class,
            'View'                                       => \Zend\View\View::class,
            'ViewFeedStrategy'                           => \Zend\View\Strategy\FeedStrategy::class,
            'ViewFeedRenderer'                           => \Zend\View\Renderer\FeedRenderer::class,
            'ViewHelperManager'                          => \Zend\View\HelperPluginManager::class,
            'ViewJsonStrategy'                           => \Zend\View\Strategy\JsonStrategy::class,
            'ViewJsonRenderer'                           => \Zend\View\Renderer\JsonRenderer::class,
            'ViewManager'                                => View\Http\ViewManager::class,
            'ViewPhpRenderer'                            => \Zend\View\Renderer\PhpRenderer::class,
            'ViewPhpRendererStrategy'                    => \Zend\View\Strategy\PhpRendererStrategy::class,
            'ViewPrefixPathStackResolver'                => \Zend\View\Resolver\PrefixPathStackResolver::class,
            'ViewRenderer'                               => \Zend\View\Renderer\PhpRenderer::class,
            'ViewResolver'                               => \Zend\View\Resolver\AggregateResolver::class,
            'ViewTemplateMapResolver'                    => \Zend\View\Resolver\TemplateMapResolver::class,
            'ViewTemplatePathStack'                      => \Zend\View\Resolver\TemplatePathStack::class,
            \Zend\View\Renderer\RendererInterface::class => \Zend\View\Renderer\PhpRenderer::class,
            \Zend\View\Resolver\ResolverInterface::class => \Zend\View\Resolver\AggregateResolver::class,
            'Zend\View\ViewHelperManager'                => \Zend\View\View::class,
        ],
        'invokables' => [],
        'factories'  => [
            'config'                                           => ConfigFactory::class,
            'PaginatorPluginManager'                           => PaginatorPluginManagerFactory::class,
            ControllerManager::class                           => ControllerManagerFactory::class,
            ControllerPluginManager::class                     => ControllerPluginManagerFactory::class,
            HttpRequest::class                                 => RequestFactory::class,
            HttpResponse::class                                => ResponseFactory::class,
            View\Http\DefaultRenderingStrategy::class          => HttpDefaultRenderingStrategyFactory::class,
            View\Http\ExceptionStrategy::class                 => HttpExceptionStrategyFactory::class,
            View\Http\InjectTemplateListener::class            => InjectTemplateListenerFactory::class,
            View\Http\RouteNotFoundStrategy::class             => HttpRouteNotFoundStrategyFactory::class,
            View\Http\ViewManager::class                       => HttpViewManagerFactory::class,
            \Zend\Mvc\Application::class                       => ApplicationFactory::class,
            \Zend\Mvc\DispatchListener::class                  => DispatchListenerFactory::class,
            \Zend\Mvc\HttpMethodListener::class                => HttpMethodListenerFactory::class,
            \Zend\Mvc\MiddlewareListener::class                => InvokableFactory::class,
            \Zend\Mvc\RouteListener::class                     => InvokableFactory::class,
            \Zend\Mvc\SendResponseListener::class              => SendResponseListenerFactory::class,
            \Zend\View\Renderer\FeedRenderer::class            => InvokableFactory::class,
            \Zend\View\Renderer\JsonRenderer::class            => InvokableFactory::class,
            \Zend\View\Renderer\PhpRenderer::class             => ViewPhpRendererFactory::class,
            \Zend\View\Resolver\AggregateResolver::class       => ViewResolverFactory::class,
            \Zend\View\Resolver\PrefixPathStackResolver::class => ViewPrefixPathStackResolverFactory::class,
            \Zend\View\Resolver\TemplateMapResolver::class     => ViewTemplateMapResolverFactory::class,
            \Zend\View\Resolver\TemplatePathStack::class       => ViewTemplatePathStackFactory::class,
            \Zend\View\Strategy\PhpRendererStrategy::class     => ViewPhpRendererStrategyFactory::class,
            \Zend\View\Strategy\FeedStrategy::class            => ViewFeedStrategyFactory::class,
            \Zend\View\Strategy\JsonStrategy::class            => ViewJsonStrategyFactory::class,
            \Zend\View\HelperPluginManager::class              => ViewHelperManagerFactory::class,
            \Zend\View\View::class                             => ViewFactory::class,
        ],
    ];

    /**
     * Create the service listener service
     *
     * Tries to get a service named ServiceListenerInterface from the service
     * locator, otherwise creates a ServiceListener instance, passing it the
     * container instance and the default service configuration, which can be
     * overridden by modules.
     *
     * It looks for the 'service_listener_options' key in the application
     * config and tries to add service/plugin managers as configured. The value
     * of 'service_listener_options' must be a list (array) which contains the
     * following keys:
     *
     * - service_manager: the name of the service manage to create as string
     * - config_key: the name of the configuration key to search for as string
     * - interface: the name of the interface that modules can implement as string
     * - method: the name of the method that modules have to implement as string
     *
     * @param  ServiceLocatorInterface  $serviceLocator
     * @return ServiceListenerInterface
     * @throws ServiceNotCreatedException for invalid ServiceListener service
     * @throws ServiceNotCreatedException For invalid configurations.
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $configuration   = $container->get('ApplicationConfig');

        $serviceListener = $container->has('ServiceListenerInterface')
            ? $container->get('ServiceListenerInterface')
            : new ServiceListener($container);

        if (! $serviceListener instanceof ServiceListenerInterface) {
            throw new ServiceNotCreatedException(
                'The service named ServiceListenerInterface must implement '
                .  ServiceListenerInterface::class
            );
        }

        $serviceListener->setDefaultServiceConfig($this->defaultServiceConfig);

        if (isset($configuration['service_listener_options'])) {
            $this->injectServiceListenerOptions($configuration['service_listener_options'], $serviceListener);
        }

        return $serviceListener;
    }

    /**
     * Validate and inject plugin manager options into the service listener.
     *
     * @param array $options
     * @param ServiceListenerInterface $serviceListener
     * @throws ServiceListenerInterface for invalid $options types
     */
    private function injectServiceListenerOptions($options, ServiceListenerInterface $serviceListener)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'The value of service_listener_options must be an array, %s given.',
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        foreach ($options as $key => $newServiceManager) {
            $this->validatePluginManagerOptions($newServiceManager, $key);

            $serviceListener->addServiceManager(
                $newServiceManager['service_manager'],
                $newServiceManager['config_key'],
                $newServiceManager['interface'],
                $newServiceManager['method']
            );
        }
    }

    /**
     * Validate the structure and types for plugin manager configuration options.
     *
     * Ensures all required keys are present in the expected types.
     *
     * @param array $options
     * @param string $name Plugin manager service name; used for exception messages
     * @throws ServiceNotCreatedException for any missing configuration options.
     * @throws ServiceNotCreatedException for configuration options of invalid types.
     */
    private function validatePluginManagerOptions($options, $name)
    {
        if (! is_array($options)) {
            throw new ServiceNotCreatedException(sprintf(
                'Plugin manager configuration for "%s" is invalid; must be an array, received "%s"',
                $name,
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        if (! isset($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'service_manager'));
        }

        if (! is_string($options['service_manager'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'service_manager',
                gettype($options['service_manager'])
            ));
        }

        if (! isset($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'config_key'));
        }

        if (! is_string($options['config_key'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'config_key',
                gettype($options['config_key'])
            ));
        }

        if (! isset($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'interface'));
        }

        if (! is_string($options['interface'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'interface',
                gettype($options['interface'])
            ));
        }

        if (! isset($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(self::MISSING_KEY_ERROR, $name, 'method'));
        }

        if (! is_string($options['method'])) {
            throw new ServiceNotCreatedException(sprintf(
                self::VALUE_TYPE_ERROR,
                'method',
                gettype($options['method'])
            ));
        }
    }
}
