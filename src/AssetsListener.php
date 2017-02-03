<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ResponseInterface;
use Zend\Http\Response\Stream as StreamResponse;
use Zend\View\Assets\Asset;
use Zend\View\Assets\AssetsManager;
use Zend\Router\RouteInterface;
use Zend\View\Resolver\ResolverInterface;
use Zend\ServiceManager\PluginManagerInterface;

/**
 * Description of DispatchAssetsListener
 *
 * @author Shiri
 */
class AssetsListener extends AbstractListenerAggregate
{
    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    /**
     * @var ResolverInterface
     */
    protected $assetsResolver;

    /**
     * @var PluginManagerInterface
     */
    protected $filterManager;

    protected $request;

    protected $routeName = 'assets';

    protected $routerCacheFolder = 'assets';

    protected $useInternalRouter = true;

    protected $router;

    protected $isCacheToPublic = true;

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'onDispatch'), 20);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_BOOTSTRAP, array($this, 'onBootstrap'), -1000);
    }

    public function onBootstrap(MvcEvent $event)
    {
        if (!$this->useInternalRouter) {
            return;
        }

        if ($this->router) {
            $router = $this->router;
        } else {
            $router = [
                'type' => \Zend\Router\Http\Segment::class,
                'options' => [
                    'route' => '/' . $this->routerCacheFolder . '[/alias-:alias][/prefix-:prefix]/:asset',
                    'constraints' => [
                        'alias'  => '[a-zA-Z][.a-zA-Z0-9_-]*',
                        'prefix' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'asset'  => '\S*',
                    ],
                    'defaults' => [
                        'alias'  => null,
                        'prefix' => null,
                    ],
                ],
            ];
        }

        $event->getRouter()->addRoute(
            end(explode('/', $this->routeName)),
            $router
        );
    }

    /**
     * Listen to the "dispatch" event
     *
     * @param  MvcEvent $event
     * @return null|Response
     */
    public function onDispatch(MvcEvent $event)
    {
        try {
            $asset = $this->detectAsset($event);
        
            if (!$asset) {
                return;
            }
            if ($asset instanceof ResponseInterface) {
                return $asset;
            }

            if (!($assetFile = $this->getAssetsResolver()->resolve($asset->getSource()))) {
                return $event->getResponse()
                        ->setStatusCode(404)
                        ->setContent(sprintf(
                            'can not resolve "%s" asset',
                            $asset->getName()
                        ));
            }

            $target = $this->filter($event, $assetFile, $asset);
            $result = $this->cache($event, $target);
            $mime = $this->getAssetsManager()->getMimeResolver()->resolve($assetFile);

            return $this->complete($event, $result, $mime);
        } catch (\Exception $ex) {
            return $event->getResponse()
                    ->setStatusCode(500)
                    ->setContent($ex->getMessage());
        }
    }

    /**
     * @param MvcEvent $event
     * @return null|array
     */
    protected function detectAsset(MvcEvent $event)
    {
        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch || $routeMatch->getMatchedRouteName() != $this->routeName) {
            return;
        }

        $aliasName = $routeMatch->getParam('alias');
        $assetName = Asset::normalizeName([
            $routeMatch->getParam('prefix'),
            $routeMatch->getParam('asset')]
        );

        if (!$aliasName) {
            return new Asset($assetName);
        }

        $alias = $this->getAssetsManager()->get($aliasName);
        if (!$alias) {
            return $event->getResponse()->setStatusCode(404)->setContent(sprintf(
                'alias "%s" not found',
                $aliasName
            ));
        }
        $asset = $alias->get($assetName);
        if (!$asset) {
            return $event->getResponse()->setStatusCode(404)->setContent(sprintf(
                'asset "%s" not found in alias "%s"',
                $assetName,
                $aliasName
            ));
        }
        return $asset;
    }

    protected function complete(MvcEvent $event, $return = null, $mimetype = null)
    {
        if (is_string($return)) {
            $response = $event->getResponse();
            $response->setContent($return);
            $contentLength = function_exists('mb_strlen') ? mb_strlen($return, '8bit') : strlen($return);
        } elseif (is_resource($return)) {
            $response = new StreamResponse();
            $response->setStream($return);
            $contentLength = fstat($return)['size'];
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$return" parameter an null or string or resource, received "%s"',
                __METHOD__,
                (is_object($return) ? get_class($return) : gettype($return))
            ));
        }

        $response->getHeaders()->clearHeaders()->addHeaders([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => $mimetype,
            'Content-Length' => $contentLength,
        ]);
        return $response;
    }

    protected function filter(MvcEvent $event, $assetFile, $assetItem)
    {
        $filters = $this->getAssetsManager()->getAssetFilters($assetItem);
        if (!$filters) {
            return fopen($assetFile, 'r');
        }

        $assetContent = file_get_contents($assetFile);
        foreach($filters as $filter) {
            if (!$filter instanceof FilterInterface) {
                $filter = $this->getFilterManager()->get($filter);
            }
            $assetContent = $filter->filter($assetContent);
        }
        return $assetContent;
    }

    protected function cache(MvcEvent $event, $source)
    {
        if (!$this->isCacheToPublic) {
            return $source;
        }

        $request = $event->getRequest();
        $targetFile = $this->getAssetsManager()->getPublicFolder() . substr($request->getRequestUri(), strlen($request->getBasePath()));

        $targetDir  = dirname($targetFile);
        if (!file_exists($targetDir) && @mkdir($targetDir, 0777, true) === false) {
            throw new Exception\RuntimeException('can not create folder for caching asset');
        }

        if (is_string($source)) {
            if (@file_put_contents($targetFile, $source) !== false) {
                return $source;
            }
            throw new Exception\RuntimeException('can not save file to cache');
        }

        if (is_resource($source)) {
            $target = fopen($targetFile, 'x+');
            if (@stream_copy_to_stream($source, $target)) {
                fclose($source);
                fseek($target, 0);
                return $target;
            }
            throw new Exception\RuntimeException('can not save file to cache');
        }

        throw new Exception\InvalidArgumentException(sprintf(
            '%s: expects "$source" parameter an string or resource, received "%s"',
            __METHOD__,
            (is_object($source) ? get_class($source) : gettype($source))
        ));
    }

    public function setCacheToPublic($flag)
    {
        $this->isCacheToPublic = (bool)$flag;
        return $this;
    }

    public function isCacheToPublic()
    {
        return $this->isCacheToPublic;
    }

    /**
     * @param string $routeName
     * @return self
     */
    public function setRouteName($routeName)
    {
        if (!is_string($routeName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects parameter an %s, received "%s"',
                __METHOD__,
                'string',
                (is_object($routeName) ? get_class($routeName) : gettype($routeName))
            ));
        }
        $this->routeName = $routeName;
        return $this;
    }

    /**
     * @param array|RouteStackInterface $router
     * @return self
     */
    public function setRouter($router)
    {
        if (!(is_array($router) || $router instanceof RouteInterface)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects parameter an %s or %s, received "%s"',
                __METHOD__,
                'array',
                RouteInterface::class,
                (is_object($router) ? get_class($router) : gettype($router))
            ));
        }
        $this->router = $router;
        return $this;
    }

    public function setRouterCacheFolder($routerCacheFolder)
    {
        $this->routerCacheFolder = trim($routerCacheFolder, '/');
        return $this;
    }

    public function setUseInternalRouter($useInternalRouter)
    {
        $this->useInternalRouter = (bool)$useInternalRouter;
        return $this;
    }

    /**
     * @return AssetsManager
     */
    public function getAssetsManager()
    {
        return $this->assetsManager;
    }

    /**
     * @param AssetsManager $assetsManager
     * @return self
     */
    public function setAssetsManager(AssetsManager $assetsManager)
    {
        $this->assetsManager = $assetsManager;
        return $this;
    }

    /**
     * @return ResolverInterface
     */
    public function getAssetsResolver()
    {
        return $this->assetsResolver;
    }

    /**
     * @param ResolverInterface $resolver
     * @return self
     */
    public function setAssetsResolver(ResolverInterface $resolver)
    {
        $this->assetsResolver = $resolver;
        return $this;
    }

    /**
     * @return PluginManagerInterface
     */
    public function getFilterManager()
    {
        return $this->filterManager;
    }

    /**
     * @param PluginManagerInterface $filterManager
     * @return self
     */
    public function setFilterManager(PluginManagerInterface $filterManager)
    {
        $this->filterManager = $filterManager;
        return $this;
    }
}
