<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc;

use Zend\Mvc\AssetsListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Exception;
use Zend\Router\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Http\PhpEnvironment\Request;
//use Zend\Http\Response as ContentResponse;
use Zend\Http\PhpEnvironment\Response as ContentResponse;
use Zend\Http\Response\Stream as StreamResponse;
use Zend\Stdlib\ArrayUtils;
use Zend\Filter\FilterPluginManager;

class AssetsListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $publicFolder = '_files/assets_cache';

    protected $routeName = 'foo';

    protected $routeUriPrefix = 'assets';
        
    /**
     * @var ServiceManager
    */
    protected $serviceManager;

    /**
     * @var AssetsListener
     */
    protected $assetsListener;

    /**
     * @var MvcEvent
     */
    protected $mvcEvent;

    public function setUp()
    {
        $this->removeDirectory();
    }

    public function tearDown()
    {
        $this->removeDirectory();
    }

    /**
     * 
     * @param string $asset
     * @param array $assetsManagerConfig
     * @return AssetsListener
     */
    protected function prepareEnvironment($asset, $assetsManagerConfig = [])
    {
        if (!$this->serviceManager) {
            $this->serviceManager = new ServiceManager([
                'services' => [
                    'config' => ['assets_manager' => ArrayUtils::merge([
                        'public_folder' => $this->publicFolder,
                        'template_resolver' => [
                            'map_resolver' => [
                                'style1.css' => __DIR__ . '\TestAsset\assets\style1.css',
                                'style2.less' => __DIR__ . '\TestAsset\assets\style2.less',
                            ],
                            'prefix_resolver' => [
                                'foo::' => __DIR__ . '\TestAsset\assets'
                            ],
                            'path_resolver' => [],
                        ],
                        'router_name' => $this->routeName,
                        'router_cache_folder' => $this->routeUriPrefix,
                        'use_internal_router' => true,
                    ], $assetsManagerConfig)],
                    'FilterManager' => new FilterPluginManager(new ServiceManager([])),
                    'Request' => new Request(),
                ],
                'factories' => [
                    'ViewAssetsResolver' => 'Zend\View\Assets\Service\AssetsResolverFactory',
                    'AssetsManager'      => 'Zend\View\Assets\Service\AssetsManagerFactory',
                    'AssetsListener'     => 'Zend\Mvc\Service\AssetsListenerFactory',
                    'MimeResolver'       => 'Zend\View\Assets\Service\MimeResolverFactory',
                    'RoutePluginManager' => 'Zend\Router\RoutePluginManagerFactory',
                    'Router'             => 'Zend\Router\RouterFactory',
                    'HttpRouter'         => 'Zend\Router\Http\HttpRouterFactory',
                    'Assets'             => 'Zend\View\Helper\Service\AssetsFactory',
                ],
            ]);
        }
        $this->assetsListener = $this->serviceManager->get('AssetsListener');
        $this->mvcEvent = $this->prepareAssetToMvcEvent($asset);
        return $this->assetsListener;
    }

    protected function prepareAssetToMvcEvent($asset)
    {
        $request = $this->serviceManager->get('Request');
        $router = $this->serviceManager->get('Router');
        
        $mvcEvent = (new MvcEvent())
            ->setResponse(new ContentResponse())
            ->setRequest($request)
            ->setRouter($router);

        if (!$asset) {
            $asset = [];
        }
        if (is_array($asset)) {
            $routeMatch = (new RouteMatch($asset))->setMatchedRouteName($this->routeName);
            return $mvcEvent->setRouteMatch($routeMatch);
        }
        
        $renderer = (new \Zend\View\Renderer\PhpRenderer())
                ->setHelperPluginManager(new \Zend\View\HelperPluginManager(new ServiceManager));
        
        $renderer->plugin('url')->setRouter($router);

        $this->assetsListener->onBootstrap($mvcEvent);

        $mimeRenderer = $this->getMockBuilder(\stdClass::class)
                         ->setMethods(['itemToString'])
                         ->getMock();
        $mimeRenderer->method('itemToString')->will($this->returnCallback(function($params) {
            return $params->href;
        }));

        $href = trim($this->serviceManager->get('Assets')
                ->setView($renderer)
                ->add($asset)
                ->setMimeRenderer('default', $mimeRenderer)
                ->render(), "\n");

        $request->setBasePath('')->setRequestUri($href)->getUri()->setPath($href);

        $routeMatch = $router->match($request);
        if (!$routeMatch) {
            $routeMatch = (new RouteMatch([]))->setMatchedRouteName($this->routeName);
        }
        $mvcEvent->setRouteMatch($routeMatch);
        return $mvcEvent;
    }

    public function testSetRouter()
    {
        $listener = new AssetsListener(new ServiceManager());

        $listener->setRouter([]);
        $listener->setRouter($this->getMock('Zend\Router\RouteInterface'));

        $this->setExpectedException(
            Exception\InvalidArgumentException::class,
            'Zend\Mvc\AssetsListener::setRouter: expects parameter an array or Zend\Router\RouteInterface, received "stdClass"'
        );
        $listener->setRouter(new \stdClass);
    }

    public function testInjectCustomRouterViaConfig()
    {
        $routeName = 'asset_route_name';
        $this->prepareEnvironment('a1', [
            'router_name' => $routeName,
            'router' => [
                'type' => \Zend\Router\Http\Literal::class,
                'options' => [
                    'route' => '/foo',
                ],
            ],
        ]);
        $this->assertTrue($this->mvcEvent->getRouter()->hasRoute($routeName));
        $this->assertInstanceOf(
            \Zend\Router\Http\Literal::class,
            $this->mvcEvent->getRouter()->getRoute($routeName)
        );
    }

    public function testInjectRouter()
    {
        $this->prepareEnvironment('a1', ['assets' => [
            'default' => [
                'a1' => ['assets' => 'foo::style1.css'],
            ],
        ]]);
        $this->assertTrue($this->mvcEvent->getRouter()->hasRoute('foo'));
    }

    public function testPrefixWithAliasToCache_StreamResponse()
    {
        $this->prepareEnvironment('a1', ['assets' => [
            'default' => [
                'a1' => ['assets' => 'foo::style1.css'],
            ],
        ]]);

        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(StreamResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $streamFile = stream_get_meta_data($response->getStream())['uri'];
        $this->assertStringEndsWith('/style1.css', $streamFile);
        $this->assertStringStartsWith('./' . $this->publicFolder, $streamFile);
        $this->assertEquals('.STYLE_1 { COLOR: 1;}', stream_get_contents($response->getStream()));
    }

    public function testPrefixWithoutAliasToCache_StreamResponse()
    {
        $this->prepareEnvironment('foo::style1.css');
        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(StreamResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $streamFile = stream_get_meta_data($response->getStream())['uri'];
        $this->assertContains('foo/', $streamFile);
        $this->assertContains($this->publicFolder, $streamFile);
        $this->assertStringEndsWith('/style1.css', $streamFile);

        $this->assertEquals('.STYLE_1 { COLOR: 1;}', stream_get_contents($response->getStream()));
    }

    public function testRenameCached()
    {
        $this->prepareEnvironment('a1', ['assets' => [
            'default' => [
                'a1' => ['assets' => [
                        'style2.css' => [
                            'source' => 'style2.less'
                        ]
                    ],
                ],
            ],
        ]]);
        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(StreamResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $streamFile = stream_get_meta_data($response->getStream())['uri'];
        $this->assertContains('a1/', $streamFile);
        $this->assertContains($this->publicFolder, $streamFile);
        $this->assertStringEndsWith('/style2.css', $streamFile);

        $this->assertEquals('.style2 { color: 22;}', stream_get_contents($response->getStream()));
    }

    public function testRenameWithPrefixCached()
    {
        $this->prepareEnvironment('a1', ['assets' => [
            'default' => [
                'a1' => ['assets' => [
                        'foo::style2.css' => [
                            'source' => 'foo::style2.less'
                        ]
                    ],
                ],
            ],
        ]]);
        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(StreamResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $streamFile = stream_get_meta_data($response->getStream())['uri'];
        $this->assertContains('a1/', $streamFile);
        $this->assertContains($this->publicFolder, $streamFile);
        $this->assertStringEndsWith('/style2.css', $streamFile);

        $this->assertEquals('.style2 { color: 22;}', stream_get_contents($response->getStream()));
    }

    public function testNoFilterStreamCached()
    {
        $this->prepareEnvironment('style1.css');
        $this->mvcEvent->getRouteMatch()
                ->setParam('alias', null)
                ->setParam('asset', 'style1.css');
        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(StreamResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $streamFile = stream_get_meta_data($response->getStream())['uri'];
        $this->assertContains($this->publicFolder, $streamFile);
        $this->assertStringEndsWith('/style1.css', $streamFile);

        $this->assertEquals('.STYLE_1 { COLOR: 1;}', stream_get_contents($response->getStream()));
    }

    public function testNoFilterStreamNotCached()
    {
        $this->prepareEnvironment('style1.css', [
            'cache_to_public' => false,
        ]);
        $this->mvcEvent->getRouteMatch()
                ->setParam('alias', null)
                ->setParam('asset', 'style1.css');
        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(StreamResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $this->assertFileNotExists($this->publicFolder . '/style1.css');
        $this->assertEquals(__DIR__ . '\TestAsset\assets\style1.css', stream_get_meta_data($response->getStream())['uri']);
        $this->assertEquals('.STYLE_1 { COLOR: 1;}', stream_get_contents($response->getStream()));
    }

    public function testFilterStringCached()
    {
        $this->prepareEnvironment('filtered', ['assets' => [
            'default' => [
                'filtered' => ['assets' => [
                        'style1.css' => [
                            'filters' => ['stringToLower'],
                        ],
                    ],
                ],
            ],
        ]]);

        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(ContentResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $this->assertFileExists($this->publicFolder);
        $this->assertFileExists($this->publicFolder . '/assets/alias-filtered/style1.css');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('.style_1 { color: 1;}', $response->getContent());
        $this->assertEquals('.style_1 { color: 1;}', file_get_contents($this->publicFolder . '/assets/alias-filtered/style1.css'));
    }

    public function testFilterStringNotCached()
    {
        $this->prepareEnvironment('filtered', [
            'cache_to_public' => false,
            'assets' => [
                'default' => [
                    'filtered' => ['assets' => [
                        'style1.css' => [
                            'filters' => ['stringToLower'],
                        ],
                    ]],
                ],
            ],
        ]);

        $response = $this->assetsListener->onDispatch($this->mvcEvent);

        $this->assertInstanceOf(ContentResponse::class, $response);

        $this->assertEquals([
            'Content-Transfer-Encoding' => 'binary',
            'Content-Type' => 'text/css',
            'Content-Length' => 21,
        ], $response->getHeaders()->toArray());

        $this->assertFileNotExists($this->publicFolder);
        $this->assertEquals('.style_1 { color: 1;}', $response->getContent());
    }

    public function test404()
    {
        $this->prepareEnvironment(null, ['assets' => [
            'default' => [
                'alias1' => ['assets' => 'style2.css'],
            ],
        ]]);

        $this->mvcEvent->getRouteMatch()
                ->setParam('alias', 'notFound');
        $response = $this->assetsListener->onDispatch($this->mvcEvent);
        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('alias "notFound" not found', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());

        $this->mvcEvent->getRouteMatch()
                ->setParam('alias', 'alias1')
                ->setParam('asset', 'notFound.css');
        $response = $this->assetsListener->onDispatch($this->mvcEvent);
        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('asset "notFound.css" not found in alias "alias1"', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());

        $this->mvcEvent->getRouteMatch()
                ->setParam('alias', null)
                ->setParam('asset', 'style2.css');
        $response = $this->assetsListener->onDispatch($this->mvcEvent);
        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('can not resolve "style2.css" asset', $response->getContent());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());
    }

    public function test500()
    {
        $mvcEvent = $this->getMock(MvcEvent::class, ['getRouteMatch', 'getResponse']);
        $mvcEvent->method('getRouteMatch')->will($this->returnCallback(function() {
            throw new \Exception('Exception 500');
        }));
        $mvcEvent->method('getResponse')->will($this->returnValue(new ContentResponse()));

        $response = (new AssetsListener())->onDispatch($mvcEvent);

        $this->assertInstanceOf(ContentResponse::class, $response);
        $this->assertEquals('Exception 500', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEmpty($response->getHeaders());
    }

    protected function removeDirectory($path = null)
    {
        if ($path === null) {
            $path = current(explode('/', $this->publicFolder));
        }
        if (!file_exists($path)) {
            return;
        }
        foreach (glob($path . '/*') as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
