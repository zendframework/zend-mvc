<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\View\Helper as ViewHelper;
use Zend\View\HelperPluginManager;

class ViewHelperManagerFactory
{
    use ViewManagerConfigTrait;

    /**
     * Create and return the view helper manager
     *
     * @param  ContainerInterface $container
     * @param string $name
     * @param array|null $options
     * @return HelperPluginManager
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, array $options = null) : HelperPluginManager
    {
        if (null === $options) {
            $config = $container->has('config') ? $container->get('config') : [];
            $options = $config['view_helpers'] ?? [];
        }
        $plugins = new HelperPluginManager($container, $options);

        // Override plugin factories
        $this->injectOverrideFactories($plugins, $container);

        return $plugins;
    }

    /**
     * Inject override factories into the plugin manager.
     *
     * @param HelperPluginManager $plugins
     * @param ContainerInterface $services
     * @return void
     */
    private function injectOverrideFactories(HelperPluginManager $plugins, ContainerInterface $services)
    {
        $override = $plugins->getAllowOverride();
        $plugins->setAllowOverride(true);
        // Configure URL view helper
        $urlFactory = $this->createUrlHelperFactory($services);
        $plugins->setFactory(ViewHelper\Url::class, $urlFactory);
        $plugins->setFactory('zendviewhelperurl', $urlFactory);

        // Configure base path helper
        $basePathFactory = $this->createBasePathHelperFactory($services);
        $plugins->setFactory(ViewHelper\BasePath::class, $basePathFactory);
        $plugins->setFactory('zendviewhelperbasepath', $basePathFactory);

        // Configure doctype view helper
        $doctypeFactory = $this->createDoctypeHelperFactory($services);
        $plugins->setFactory(ViewHelper\Doctype::class, $doctypeFactory);
        $plugins->setFactory('zendviewhelperdoctype', $doctypeFactory);
        $plugins->setAllowOverride($override);
    }

    /**
     * Create and return a factory for creating a URL helper.
     *
     * Retrieves the application and router from the servicemanager,
     * and the route match from the MvcEvent composed by the application,
     * using them to configure the helper.
     *
     * @param ContainerInterface $services
     * @return callable
     */
    private function createUrlHelperFactory(ContainerInterface $services)
    {
        return function () use ($services) {
            $helper = new ViewHelper\Url;
            $helper->setRouter($services->get('Zend\Mvc\Router'));

            return $helper;
        };
    }

    /**
     * Create and return a factory for creating a BasePath helper.
     *
     * Uses configuration and request services to configure the helper.
     *
     * @param ContainerInterface $container
     * @return callable
     */
    private function createBasePathHelperFactory(ContainerInterface $container)
    {
        return function () use ($container) {
            $helper = new ViewHelper\BasePath;

            $viewConfig = $this->getConfig($container);

            if (isset($viewConfig['base_path'])) {
                $helper->setBasePath($viewConfig['base_path']);
                return $helper;
            }

            return $helper;
        };
    }

    /**
     * Create and return a Doctype helper factory.
     *
     * Other view helpers depend on this to decide which spec to generate their tags
     * based on. This is why it must be set early instead of later in the layout phtml.
     *
     * @param ContainerInterface $container
     * @return callable
     */
    private function createDoctypeHelperFactory(ContainerInterface $container)
    {
        return function () use ($container) {
            $helper = new ViewHelper\Doctype;

            $viewConfig = $this->getConfig($container);
            if (isset($viewConfig['doctype']) && $viewConfig['doctype']) {
                $helper->setDoctype($viewConfig['doctype']);
            }
            return $helper;
        };
    }
}
