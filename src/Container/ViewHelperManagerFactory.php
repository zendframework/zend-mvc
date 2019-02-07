<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\Router\RouteMatch;
use Zend\View\Helper\BasePath;
use Zend\View\Helper\Doctype;
use Zend\View\Helper\Url;
use Zend\View\HelperPluginManager;

use function is_callable;

class ViewHelperManagerFactory
{
    public function __invoke(ContainerInterface $container) : HelperPluginManager
    {
        return new HelperPluginManager($container, $this->getConfig($container));
    }

    public function getConfig(ContainerInterface $container) : array
    {
        $helpersConfig = [];
        if ($container->has('config')) {
            $helpersConfig = $container->get('config')['view_helpers'] ?? [];
        }
        return $this->injectOverrideFactories($helpersConfig, $container);
    }

    private function injectOverrideFactories(array $config, ContainerInterface $container) : array
    {
        $config['aliases']['zendviewhelperurl'] = Url::class;
        $config['factories'][Url::class]        = $this->createUrlHelperFactory();

        // Configure base path helper
        $config['aliases']['zendviewhelperbasepath'] = BasePath::class;
        $config['factories'][BasePath::class]        = $this->createBasePathHelperFactory();

        // Configure doctype view helper
        $config['aliases']['zendviewhelperdoctype'] = Doctype::class;
        $config['factories'][Doctype::class]        = $this->createDoctypeHelperFactory();

        return $config;
    }

    /**
     * Create and return a factory for creating a URL helper.
     *
     * Retrieves the application and router from the container,
     * and the route match from the MvcEvent composed by the application,
     * using them to configure the helper.
     */
    private function createUrlHelperFactory() : callable
    {
        return function (ContainerInterface $container) {
            $helper = new Url();
            $helper->setRouter($container->get('HttpRouter'));

            $match = $container->get('Application')
                ->getMvcEvent()
                ->getRouteMatch();

            if ($match instanceof RouteMatch) {
                $helper->setRouteMatch($match);
            }

            return $helper;
        };
    }

    /**
     * Create and return a factory for creating a BasePath helper.
     *
     * Uses configuration and request services to configure the helper.
     *
     */
    private function createBasePathHelperFactory() : callable
    {
        return function (ContainerInterface $container) {
            $config = $container->has('config') ? $container->get('config') : [];
            $helper = new BasePath();

            if (isset($config['view_manager']) && isset($config['view_manager']['base_path'])) {
                $helper->setBasePath($config['view_manager']['base_path']);
                return $helper;
            }

            $request = $container->get('Request');

            if (is_callable([$request, 'getBasePath'])) {
                $helper->setBasePath($request->getBasePath());
            }

            return $helper;
        };
    }

    /**
     * Create and return a Doctype helper factory.
     *
     * Other view helpers depend on this to decide which spec to generate their tags
     * based on. This is why it must be set early instead of later in the layout phtml.
     */
    private function createDoctypeHelperFactory() : callable
    {
        return function (ContainerInterface $container) {
            $config = $container->has('config') ? $container->get('config') : [];
            $config = $config['view_manager'] ?? [];
            $helper = new Doctype();
            if (isset($config['doctype']) && $config['doctype']) {
                $helper->setDoctype($config['doctype']);
            }
            return $helper;
        };
    }
}
