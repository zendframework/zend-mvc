<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionProperty;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Application;
use Zend\Mvc\Container\ViewHelperManagerFactory;
use Zend\Mvc\MvcEvent;
use Zend\Router\Http\RouteMatch;
use Zend\Router\RouteStackInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Helper\BasePath;
use Zend\View\Helper\Doctype;
use Zend\View\Helper\HelperInterface;
use Zend\View\Helper\Url;
use Zend\View\HelperPluginManager;
use ZendTest\Mvc\ContainerTrait;

use function array_unshift;
use function is_callable;

/**
 * @covers \Zend\Mvc\Container\ViewHelperManagerFactory
 */
class ViewHelperManagerFactoryTest extends TestCase
{
    use ContainerTrait;

    /** @var ObjectProphecy */
    private $container;
    /** @var ViewHelperManagerFactory */
    private $factory;
    /** @var ServiceManager */
    private $services;

    public function setUp() : void
    {
        $this->container = $this->mockContainerInterface();
        $this->services  = new ServiceManager();
        $this->factory   = new ViewHelperManagerFactory();
    }

    /**
     * @return array
     */
    public function emptyConfiguration()
    {
        return [
            'no-config'                => [[]],
            'view-manager-config-only' => [['view_manager' => []]],
            'empty-doctype-config'     => [['view_manager' => ['doctype' => null]]],
        ];
    }

    public function testCanConfigureFromMainConfigService()
    {
        $helperMock             = $this->prophesize(HelperInterface::class)->reveal();
        $config['view_helpers'] = [
            'services' => [
                'Foo' => $helperMock,
            ],
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $plugins = $this->factory->__invoke($this->container->reveal());
        $this->assertSame($helperMock, $plugins->get('Foo'));
    }

    /**
     * @dataProvider emptyConfiguration
     * @param  array $config
     * @return void
     */
    public function testDoctypeFactoryDoesNotRaiseErrorOnMissingConfiguration($config)
    {
        $this->services->setService('config', $config);
        $manager = $this->factory->__invoke($this->services, 'doctype');
        $this->assertInstanceof(HelperPluginManager::class, $manager);
        $doctype = $manager->get('doctype');
        $this->assertInstanceof(Doctype::class, $doctype);
    }

    public function urlHelperNames()
    {
        return [
            ['url'],
            ['Url'],
            [Url::class],
            ['zendviewhelperurl'],
        ];
    }

    /**
     * @group 71
     * @dataProvider urlHelperNames
     */
    public function testUrlHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name)
    {
        $routeMatch = $this->prophesize(RouteMatch::class)->reveal();
        $mvcEvent   = $this->prophesize(MvcEvent::class);
        $mvcEvent->getRouteMatch()->willReturn($routeMatch);

        $application = $this->prophesize(Application::class);
        $application->getMvcEvent()->willReturn($mvcEvent->reveal());

        $router = $this->prophesize(RouteStackInterface::class)->reveal();

        $this->services->setService('HttpRouter', $router);
        $this->services->setService('Router', $router);
        $this->services->setService('Application', $application->reveal());
        $this->services->setService('config', []);

        $manager = $this->factory->__invoke($this->services, HelperPluginManager::class);
        /** @var Url $helper */
        $helper = $manager->get($name);

        $routeMatchProp = new ReflectionProperty(Url::class, 'routeMatch');
        $routeMatchProp->setAccessible(true);
        $this->assertSame($routeMatch, $routeMatchProp->getValue($helper), 'Route match was not injected');

        $routerProp = new ReflectionProperty(Url::class, 'router');
        $routerProp->setAccessible(true);
        $this->assertSame($router, $routerProp->getValue($helper), 'Router was not injected');
    }

    public function basePathConfiguration()
    {
        $names = ['basepath', 'basePath', 'BasePath', BasePath::class, 'zendviewhelperbasepath'];

        $configurations = [
            'hard-coded'   => [
                [
                    'config' => [
                        'view_manager' => [
                            'base_path' => '/foo/baz',
                        ],
                    ],
                ],
                '/foo/baz',
            ],
            'request-base' => [
                [
                    'config'  => [], // fails creating plugin manager without this
                    'Request' => function () {
                        $request = $this->prophesize(Request::class);
                        $request->getBasePath()->willReturn('/foo/bat');
                        return $request->reveal();
                    },
                ],
                '/foo/bat',
            ],
        ];

        foreach ($names as $name) {
            foreach ($configurations as $testcase => $arguments) {
                array_unshift($arguments, $name);
                $testcase .= '-' . $name;
                yield $testcase => $arguments;
            }
        }
    }

    /**
     * @group 71
     * @dataProvider basePathConfiguration
     */
    public function testBasePathHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name, array $services, $expected)
    {
        foreach ($services as $key => $value) {
            if (is_callable($value)) {
                $this->services->setFactory($key, $value);
                continue;
            }

            $this->services->setService($key, $value);
        }

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper  = $plugins->get($name);
        $this->assertInstanceof(BasePath::class, $helper);
        $this->assertEquals($expected, $helper());
    }

    public function doctypeHelperNames()
    {
        return [
            ['doctype'],
            ['Doctype'],
            [Doctype::class],
            ['zendviewhelperdoctype'],
        ];
    }

    /**
     * @group 71
     * @dataProvider doctypeHelperNames
     */
    public function testDoctypeHelperFactoryCanBeInvokedViaShortNameOrFullClassName($name)
    {
        $this->services->setService('config', [
            'view_manager' => [
                'doctype' => Doctype::HTML5,
            ],
        ]);

        $plugins = $this->factory->__invoke($this->services, HelperPluginManager::class);
        $helper  = $plugins->get($name);
        $this->assertInstanceof(Doctype::class, $helper);
        $this->assertEquals('<!DOCTYPE html>', (string) $helper);
    }
}
