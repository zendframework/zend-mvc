<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Router\Http;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as Request;
use Zend\Mvc\Router\RoutePluginManager;
use Zend\Mvc\Router\Http\Chain;
use Zend\ServiceManager\ServiceManager;
use ZendTest\Mvc\Router\FactoryTester;

class ChainTest extends TestCase
{
    public static function getRoute()
    {
        $routePlugins = new RoutePluginManager(new ServiceManager());

        return new Chain(
            [
                [
                    'type'    => 'Zend\Mvc\Router\Http\Segment',
                    'options' => [
                        'route'    => '/:controller',
                        'defaults' => [
                            'controller' => 'foo',
                        ],
                    ],
                ],
                [
                    'type'    => 'Zend\Mvc\Router\Http\Segment',
                    'options' => [
                        'route'    => '/:bar',
                        'defaults' => [
                            'bar' => 'bar',
                        ],
                    ],
                ],
                [
                    'type' => 'Zend\Mvc\Router\Http\Wildcard',
                ],
            ],
            $routePlugins
        );
    }

    public static function getRouteWithOptionalParam()
    {
        $routePlugins = new RoutePluginManager(new ServiceManager());

        return new Chain(
            [
                [
                    'type'    => 'Zend\Mvc\Router\Http\Segment',
                    'options' => [
                        'route'    => '/:controller',
                        'defaults' => [
                            'controller' => 'foo',
                        ],
                    ],
                ],
                [
                    'type'    => 'Zend\Mvc\Router\Http\Segment',
                    'options' => [
                        'route'    => '[/:bar]',
                        'defaults' => [
                            'bar' => 'bar',
                        ],
                    ],
                ],
            ],
            $routePlugins
        );
    }

    public static function routeProvider()
    {
        return [
            'simple-match' => [
                self::getRoute(),
                '/foo/bar',
                null,
                [
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ],
            ],
            'offset-skips-beginning' => [
                self::getRoute(),
                '/baz/foo/bar',
                4,
                [
                    'controller' => 'foo',
                    'bar'        => 'bar',
                ],
            ],
            'parameters-are-used-only-once' => [
                self::getRoute(),
                '/foo/baz',
                null,
                [
                    'controller' => 'foo',
                    'bar' => 'baz',
                ],
            ],
            'optional-parameter' => [
                self::getRouteWithOptionalParam(),
                '/foo/baz',
                null,
                [
                    'controller' => 'foo',
                    'bar' => 'baz',
                ],
            ],
            'optional-parameter-empty' => [
                self::getRouteWithOptionalParam(),
                '/foo',
                null,
                [
                    'controller' => 'foo',
                    'bar' => 'bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param        Chain   $route
     * @param        string  $path
     * @param        integer $offset
     * @param        array   $params
     */
    public function testMatching(Chain $route, $path, $offset, array $params = null)
    {
        $request = new Request();
        $request->setUri('http://example.com' . $path);
        $match = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf('Zend\Mvc\Router\Http\RouteMatch', $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @dataProvider routeProvider
     * @param        Chain   $route
     * @param        string  $path
     * @param        integer $offset
     * @param        string  $routeName
     * @param        array   $params
     */
    public function testAssembling(Chain $route, $path, $offset, array $params = null)
    {
        if ($params === null) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble($params);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Zend\Mvc\Router\Http\Chain',
            [
                'routes'        => 'Missing "routes" in options array',
                'route_plugins' => 'Missing "route_plugins" in options array',
            ],
            [
                'routes'        => [],
                'route_plugins' => new RoutePluginManager(new ServiceManager()),
            ]
        );
    }
}
