<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Container\ApplicationFactory;
use Zend\Mvc\Container\ApplicationListenerProviderFactory;
use Zend\Mvc\Container\ControllerManagerFactory;
use Zend\Mvc\Container\ControllerPluginManagerFactory;
use Zend\Mvc\Container\RoutePluginManagerFactory;
use Zend\Mvc\Container\ViewHelperManagerFactory;
use Zend\Mvc\Controller\PluginManager;
use Zend\Mvc\Service\DispatchListenerFactory;
use Zend\Mvc\Service\EventManagerFactory;
use Zend\Mvc\Service\HttpDefaultRenderingStrategyFactory;
use Zend\Mvc\Service\HttpExceptionStrategyFactory;
use Zend\Mvc\Service\HttpMethodListenerFactory;
use Zend\Mvc\Service\HttpRouteNotFoundStrategyFactory;
use Zend\Mvc\Service\HttpViewManagerFactory;
use Zend\Mvc\Service\InjectTemplateListenerFactory;
use Zend\Mvc\Service\RequestFactory;
use Zend\Mvc\Service\ResponseFactory;
use Zend\Mvc\Service\SendResponseListenerFactory;
use Zend\Mvc\Service\ViewFactory;
use Zend\Mvc\Service\ViewFeedStrategyFactory;
use Zend\Mvc\Service\ViewJsonStrategyFactory;
use Zend\Mvc\Service\ViewManagerFactory;
use Zend\Mvc\Service\ViewPhpRendererFactory;
use Zend\Mvc\Service\ViewPhpRendererStrategyFactory;
use Zend\Mvc\Service\ViewPrefixPathStackResolverFactory;
use Zend\Mvc\Service\ViewResolverFactory;
use Zend\Mvc\Service\ViewTemplateMapResolverFactory;
use Zend\Mvc\Service\ViewTemplatePathStackFactory;
use Zend\Mvc\View\Http\DefaultRenderingStrategy;
use Zend\Mvc\View\Http\InjectTemplateListener;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\View\Renderer\FeedRenderer;
use Zend\View\Renderer\JsonRenderer;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Renderer\RendererInterface;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Resolver\TemplateMapResolver;
use Zend\View\Resolver\TemplatePathStack;
use Zend\View\Strategy\PhpRendererStrategy;
use Zend\View\View;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        // @TODO move RoutePluginManager to zend-router. Won't work due to config merge order
        return [
            'aliases'   => [
                'application'                      => 'Application',
                'Config'                           => 'config',
                'configuration'                    => 'config',
                'Configuration'                    => 'config',
                'HttpDefaultRenderingStrategy'     => DefaultRenderingStrategy::class,
                'MiddlewareListener'               => MiddlewareListener::class,
                'request'                          => 'Request',
                'response'                         => 'Response',
                'RouteListener'                    => RouteListener::class,
                'SendResponseListener'             => SendResponseListener::class,
                'View'                             => View::class,
                'ViewFeedRenderer'                 => FeedRenderer::class,
                'ViewJsonRenderer'                 => JsonRenderer::class,
                'ViewPhpRendererStrategy'          => PhpRendererStrategy::class,
                'ViewPhpRenderer'                  => PhpRenderer::class,
                'ViewRenderer'                     => PhpRenderer::class,
                'SharedEventManager'               => SharedEventManager::class,
                SharedEventManagerInterface::class => SharedEventManager::class,
                EventManagerInterface::class       => 'EventManager',
                PluginManager::class               => 'ControllerPluginManager',
                InjectTemplateListener::class      => 'InjectTemplateListener',
                RendererInterface::class           => PhpRenderer::class,
                TemplateMapResolver::class         => 'ViewTemplateMapResolver',
                TemplatePathStack::class           => 'ViewTemplatePathStack',
                AggregateResolver::class           => 'ViewResolver',
                ResolverInterface::class           => 'ViewResolver',
            ],
            'factories' => [
                'Application'                      => ApplicationFactory::class,
                'ControllerManager'                => ControllerManagerFactory::class,
                'ControllerPluginManager'          => ControllerPluginManagerFactory::class,
                'DispatchListener'                 => DispatchListenerFactory::class,
                'EventManager'                     => EventManagerFactory::class,
                'HttpExceptionStrategy'            => HttpExceptionStrategyFactory::class,
                'HttpMethodListener'               => HttpMethodListenerFactory::class,
                'HttpRouteNotFoundStrategy'        => HttpRouteNotFoundStrategyFactory::class,
                'HttpViewManager'                  => HttpViewManagerFactory::class,
                'InjectTemplateListener'           => InjectTemplateListenerFactory::class,
                'Request'                          => RequestFactory::class,
                'Response'                         => ResponseFactory::class,
                'RoutePluginManager'               => RoutePluginManagerFactory::class,
                'ViewHelperManager'                => ViewHelperManagerFactory::class,
                DefaultRenderingStrategy::class    => HttpDefaultRenderingStrategyFactory::class,
                'ViewFeedStrategy'                 => ViewFeedStrategyFactory::class,
                'ViewJsonStrategy'                 => ViewJsonStrategyFactory::class,
                'ViewManager'                      => ViewManagerFactory::class,
                'ViewResolver'                     => ViewResolverFactory::class,
                'ViewTemplateMapResolver'          => ViewTemplateMapResolverFactory::class,
                'ViewTemplatePathStack'            => ViewTemplatePathStackFactory::class,
                'ViewPrefixPathStackResolver'      => ViewPrefixPathStackResolverFactory::class,
                ApplicationListenerProvider::class => ApplicationListenerProviderFactory::class,
                FeedRenderer::class                => InvokableFactory::class,
                JsonRenderer::class                => InvokableFactory::class,
                MiddlewareListener::class          => InvokableFactory::class,
                PhpRenderer::class                 => ViewPhpRendererFactory::class,
                PhpRendererStrategy::class         => ViewPhpRendererStrategyFactory::class,
                RouteListener::class               => InvokableFactory::class,
                SendResponseListener::class        => SendResponseListenerFactory::class,
                SharedEventManager::class          => InvokableFactory::class,
                View::class                        => ViewFactory::class,
            ],
            'shared'    => [
                'EventManager' => false,
            ],
        ];
    }
}
