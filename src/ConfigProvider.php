<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Zend\Mvc;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Container\ApplicationFactory;
use Zend\Mvc\Container\ControllerManagerFactory;
use Zend\Mvc\Container\ControllerPluginManagerFactory;
use Zend\Mvc\Container\DefaultRenderingStrategyFactory;
use Zend\Mvc\Container\DispatchListenerFactory;
use Zend\Mvc\Container\EventManagerFactory;
use Zend\Mvc\Container\ExceptionStrategyFactory;
use Zend\Mvc\Container\HttpMethodListenerFactory;
use Zend\Mvc\Container\InjectTemplateListenerFactory;
use Zend\Mvc\Container\RouteNotFoundStrategyFactory;
use Zend\Mvc\Container\ViewFactory;
use Zend\Mvc\Container\ViewFeedStrategyFactory;
use Zend\Mvc\Container\ViewHelperManagerFactory;
use Zend\Mvc\Container\ViewJsonStrategyFactory;
use Zend\Mvc\Container\ViewPhpRendererFactory;
use Zend\Mvc\Container\ViewPhpRendererStrategyFactory;
use Zend\Mvc\Container\ViewPrefixPathStackResolverFactory;
use Zend\Mvc\Container\ViewResolverFactory;
use Zend\Mvc\Container\ViewTemplateMapResolverFactory;
use Zend\Mvc\Container\ViewTemplatePathStackFactory;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Controller\PluginManager as ControllerPluginManager;
use Zend\Mvc\View\Http\DefaultRenderingStrategy;
use Zend\Mvc\View\Http\ExceptionStrategy;
use Zend\Mvc\View\Http\InjectTemplateListener;
use Zend\Mvc\View\Http\RouteNotFoundStrategy;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Router\RoutePluginManager;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\View\HelperPluginManager;
use Zend\View\Renderer\FeedRenderer;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Renderer\RendererInterface;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\PrefixPathStackResolver;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Resolver\TemplateMapResolver;
use Zend\View\Resolver\TemplatePathStack;
use Zend\View\Strategy\FeedStrategy;
use Zend\View\Strategy\JsonStrategy;
use Zend\View\Strategy\PhpRendererStrategy;
use Zend\View\View;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

    public function getDependencyConfig() : array
    {
        return [
            'aliases' => [
                'ControllerManager' => ControllerManager::class,
                'ControllerPluginManager' => ControllerPluginManager::class,
                'DispatchListener' => DispatchListener::class,
                'HttpDefaultRenderingStrategy' => DefaultRenderingStrategy::class,
                'HttpExceptionStrategy' => ExceptionStrategy::class,
                'HttpMethodListener' => HttpMethodListener::class,
                'HttpRouteNotFoundStrategy' => RouteNotFoundStrategy::class,
                'HttpViewManager' => ViewManager::class,
                'InjectTemplateListener' => InjectTemplateListener::class,
                'MiddlewareListener' => MiddlewareListener::class,
                'RouteListener' => RouteListener::class,
                'RoutePluginManager' => RoutePluginManager::class,
                'View' => View::class,
                'ViewFeedRenderer' => FeedRenderer::class,
                'ViewFeedStrategy' => FeedStrategy::class,
                'ViewHelperManager' => HelperPluginManager::class,
                'ViewJsonRenderer' => JsonRenderer::class,
                'ViewJsonStrategy' => JsonStrategy::class,
                'ViewManager' => ViewManager::class,
                'ViewPhpRenderer' => PhpRenderer::class,
                'ViewPhpRendererStrategy' => PhpRendererStrategy::class,
                'ViewPrefixPathStackResolver' => PrefixPathStackResolver::class,
                'ViewRenderer' => PhpRenderer::class,
                'ViewResolver' => 'Zend\Mvc\View\Resolver',
                'ViewTemplateMapResolver' => TemplateMapResolver::class,
                'ViewTemplatePathStack' => TemplatePathStack::class,
                'EventManagerInterface' => 'EventManager',
                EventManagerInterface::class => 'EventManager',
                'SharedEventManager' => SharedEventManager::class,
                'SharedEventManagerInterface' => SharedEventManager::class,
                'Zend\Mvc\Router' => 'HttpRouter',
                AggregateResolver::class => 'Zend\Mvc\View\Resolver',
                EventManagerInterface::class => 'EventManager',
                RendererInterface::class => PhpRenderer::class,
                ResolverInterface::class => 'Zend\Mvc\View\Resolver',
                SharedEventManagerInterface::class => SharedEventManager::class,
            ],
            'factories' => [
                'EventManager' => EventManagerFactory::class,
                'Zend\Mvc\View\Resolver' => ViewResolverFactory::class,
                Application::class => ApplicationFactory::class,
                ControllerManager::class => ControllerManagerFactory::class,
                ControllerPluginManager::class => ControllerPluginManagerFactory::class,
                DefaultRenderingStrategy::class => DefaultRenderingStrategyFactory::class,
                DispatchListener::class => DispatchListenerFactory::class,
                ExceptionStrategy::class => ExceptionStrategyFactory::class,
                FeedRenderer::class => InvokableFactory::class,
                FeedStrategy::class => ViewFeedStrategyFactory::class,
                HelperPluginManager::class => ViewHelperManagerFactory::class,
                HttpMethodListener::class => HttpMethodListenerFactory::class,
                InjectTemplateListener::class => InjectTemplateListenerFactory::class,
                JsonRenderer::class => InvokableFactory::class,
                JsonStrategy::class => ViewJsonStrategyFactory::class,
                MiddlewareListener::class => InvokableFactory::class,
                PhpRenderer::class => ViewPhpRendererFactory::class,
                PhpRendererStrategy::class => ViewPhpRendererStrategyFactory::class,
                PrefixPathStackResolver::class => ViewPrefixPathStackResolverFactory::class,
                RouteListener::class => InvokableFactory::class,
                RouteNotFoundStrategy::class => RouteNotFoundStrategyFactory::class,
                SharedEventManager::class => InvokableFactory::class,
                TemplateMapResolver::class => ViewTemplateMapResolverFactory::class,
                TemplatePathStack::class => ViewTemplatePathStackFactory::class,
                View::class => ViewFactory::class,
                ViewManager::class => InvokableFactory::class,
            ],
            'shared' => [
                'EventManager' => false,
            ],
        ];
    }
}
