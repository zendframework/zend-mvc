<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Router;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Di\Di;
use Zend\Mvc\Router\RoutePluginManager;
use Zend\ServiceManager\Di\DiAbstractServiceFactory;

/**
 * @group      Zend_Router
 */
class RoutePluginManagerTest extends TestCase
{
    public function testLoadNonExistentRoute()
    {
        $routes = new RoutePluginManager();
        $this->setExpectedException('Zend\ServiceManager\Exception\ServiceNotFoundException');
        $routes->get('foo');
    }

    public function testCanLoadAnyRoute()
    {
        $routes = new RoutePluginManager();
        $routes->setInvokableClass('DummyRoute', 'ZendTest\Mvc\Router\TestAsset\DummyRoute');
        $route = $routes->get('DummyRoute');

        $this->assertInstanceOf('ZendTest\Mvc\Router\TestAsset\DummyRoute', $route);
    }

    public function shippedRoutes()
    {
        return [
            'hostname' => ['Zend\Mvc\Router\Http\Hostname', ['route' => 'example.com']],
            'literal'  => ['Zend\Mvc\Router\Http\Literal', ['route' => '/example']],
            'regex'    => ['Zend\Mvc\Router\Http\Regex', ['regex' => '[a-z]+', 'spec' => '%s']],
            'scheme'   => ['Zend\Mvc\Router\Http\Scheme', ['scheme' => 'http']],
            'segment'  => ['Zend\Mvc\Router\Http\Segment', ['route' => '/:segment']],
            'wildcard' => ['Zend\Mvc\Router\Http\Wildcard', []],
            //'query'    => array('Zend\Mvc\Router\Http\Query', array()),
            'method'   => ['Zend\Mvc\Router\Http\Method', ['verb' => 'GET']],
        ];
    }

    /**
     * @dataProvider shippedRoutes
     */
    public function testDoesNotInvokeDiForShippedRoutes($routeName, $options)
    {
        // Setup route plugin manager
        $routes = new RoutePluginManager();
        foreach ($this->shippedRoutes() as $name => $info) {
            $routes->setInvokableClass($name, $info[0]);
        }

        // Add DI abstract factory
        $di                = new Di;
        $diAbstractFactory = new DiAbstractServiceFactory($di, DiAbstractServiceFactory::USE_SL_BEFORE_DI);
        $routes->addAbstractFactory($diAbstractFactory);

        $this->assertTrue($routes->has($routeName));

        try {
            $route = $routes->get($routeName, $options);
            $this->assertInstanceOf($routeName, $route);
        } catch (\Exception $e) {
            $messages = [];
            do {
                $messages[] = $e->getMessage() . "\n" . $e->getTraceAsString();
            } while ($e = $e->getPrevious());
            $this->fail(implode("\n\n", $messages));
        }
    }

    /**
     * @dataProvider shippedRoutes
     */
    public function testDoesNotInvokeDiForShippedRoutesUsingShortName($routeName, $options)
    {
        // Setup route plugin manager
        $routes = new RoutePluginManager();
        foreach ($this->shippedRoutes() as $name => $info) {
            $routes->setInvokableClass($name, $info[0]);
        }

        // Add DI abstract factory
        $di                = new Di;
        $diAbstractFactory = new DiAbstractServiceFactory($di, DiAbstractServiceFactory::USE_SL_BEFORE_DI);
        $routes->addAbstractFactory($diAbstractFactory);

        $shortName = substr($routeName, strrpos($routeName, '\\') + 1);

        $this->assertTrue($routes->has($shortName));

        try {
            $route = $routes->get($shortName, $options);
            $this->assertInstanceOf($routeName, $route);
        } catch (\Exception $e) {
            $messages = [];
            do {
                $messages[] = $e->getMessage() . "\n" . $e->getTraceAsString();
            } while ($e = $e->getPrevious());
            $this->fail(implode("\n\n", $messages));
        }
    }
}
