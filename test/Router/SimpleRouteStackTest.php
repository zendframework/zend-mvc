<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Router;

use ArrayIterator;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Router\RoutePluginManager;
use Zend\Mvc\Router\SimpleRouteStack;
use Zend\Stdlib\Request;

class SimpleRouteStackTest extends TestCase
{
    public function testSetRoutePluginManager()
    {
        $routes = new RoutePluginManager();
        $stack  = new SimpleRouteStack();
        $stack->setRoutePluginManager($routes);

        $this->assertEquals($routes, $stack->getRoutePluginManager());
    }

    public function testAddRoutesWithInvalidArgument()
    {
        $this->setExpectedException('Zend\Mvc\Router\Exception\InvalidArgumentException', 'addRoutes expects an array or Traversable set of routes');
        $stack = new SimpleRouteStack();
        $stack->addRoutes('foo');
    }

    public function testAddRoutesAsArray()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute()
        ]);

        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $stack->match(new Request()));
    }

    public function testAddRoutesAsTraversable()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoutes(new ArrayIterator([
            'foo' => new TestAsset\DummyRoute()
        ]));

        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $stack->match(new Request()));
    }

    public function testSetRoutesWithInvalidArgument()
    {
        $this->setExpectedException('Zend\Mvc\Router\Exception\InvalidArgumentException', 'addRoutes expects an array or Traversable set of routes');
        $stack = new SimpleRouteStack();
        $stack->setRoutes('foo');
    }

    public function testSetRoutesAsArray()
    {
        $stack = new SimpleRouteStack();
        $stack->setRoutes([
            'foo' => new TestAsset\DummyRoute()
        ]);

        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $stack->match(new Request()));

        $stack->setRoutes([]);

        $this->assertSame(null, $stack->match(new Request()));
    }

    public function testSetRoutesAsTraversable()
    {
        $stack = new SimpleRouteStack();
        $stack->setRoutes(new ArrayIterator([
            'foo' => new TestAsset\DummyRoute()
        ]));

        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $stack->match(new Request()));

        $stack->setRoutes(new ArrayIterator([]));

        $this->assertSame(null, $stack->match(new Request()));
    }

    public function testremoveRouteAsArray()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoutes([
            'foo' => new TestAsset\DummyRoute()
        ]);

        $this->assertEquals($stack, $stack->removeRoute('foo'));
        $this->assertNull($stack->match(new Request()));
    }

    public function testAddRouteWithInvalidArgument()
    {
        $this->setExpectedException('Zend\Mvc\Router\Exception\InvalidArgumentException', 'Route definition must be an array or Traversable object');
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', 'bar');
    }

    public function testAddRouteAsArrayWithoutOptions()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', [
            'type' => '\ZendTest\Mvc\Router\TestAsset\DummyRoute'
        ]);

        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithOptions()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', [
            'type'    => '\ZendTest\Mvc\Router\TestAsset\DummyRoute',
            'options' => []
        ]);

        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $stack->match(new Request()));
    }

    public function testAddRouteAsArrayWithoutType()
    {
        $this->setExpectedException('Zend\Mvc\Router\Exception\InvalidArgumentException', 'Missing "type" option');
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', []);
    }

    public function testAddRouteAsArrayWithPriority()
    {
        $stack = new SimpleRouteStack();

        $stack->addRoute('foo', [
            'type'     => '\ZendTest\Mvc\Router\TestAsset\DummyRouteWithParam',
            'priority' => 2
        ])->addRoute('bar', [
            'type'     => '\ZendTest\Mvc\Router\TestAsset\DummyRoute',
            'priority' => 1
        ]);

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    public function testAddRouteWithPriority()
    {
        $stack = new SimpleRouteStack();

        $route = new TestAsset\DummyRouteWithParam();
        $route->priority = 2;
        $stack->addRoute('baz', $route);

        $stack->addRoute('foo', [
            'type'     => '\ZendTest\Mvc\Router\TestAsset\DummyRoute',
            'priority' => 1
        ]);

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    public function testAddRouteAsTraversable()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new ArrayIterator([
            'type' => '\ZendTest\Mvc\Router\TestAsset\DummyRoute'
        ]));

        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $stack->match(new Request()));
    }

    public function testAssemble()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals('', $stack->assemble([], ['name' => 'foo']));
    }

    public function testAssembleWithoutNameOption()
    {
        $this->setExpectedException('Zend\Mvc\Router\Exception\InvalidArgumentException', 'Missing "name" option');
        $stack = new SimpleRouteStack();
        $stack->assemble();
    }

    public function testAssembleNonExistentRoute()
    {
        $this->setExpectedException('Zend\Mvc\Router\Exception\RuntimeException', 'Route with name "foo" not found');
        $stack = new SimpleRouteStack();
        $stack->assemble([], ['name' => 'foo']);
    }

    public function testDefaultParamIsAddedToMatch()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    public function testDefaultParamDoesNotOverrideParam()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->match(new Request())->getParam('foo'));
    }

    public function testDefaultParamIsUsedForAssembling()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'bar');

        $this->assertEquals('bar', $stack->assemble([], ['name' => 'foo']));
    }

    public function testDefaultParamDoesNotOverrideParamForAssembling()
    {
        $stack = new SimpleRouteStack();
        $stack->addRoute('foo', new TestAsset\DummyRouteWithParam());
        $stack->setDefaultParam('foo', 'baz');

        $this->assertEquals('bar', $stack->assemble(['foo' => 'bar'], ['name' => 'foo']));
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Zend\Mvc\Router\SimpleRouteStack',
            [],
            [
                'route_plugins'  => new RoutePluginManager(),
                'routes'         => [],
                'default_params' => []
            ]
        );
    }

    public function testGetRoutes()
    {
        $stack = new SimpleRouteStack();
        $this->assertInstanceOf('Traversable', $stack->getRoutes());
    }

    public function testGetRouteByName()
    {
        $stack = new SimpleRouteStack();
        $route = new TestAsset\DummyRoute();
        $stack->addRoute('foo', $route);

        $this->assertEquals($route, $stack->getRoute('foo'));
    }

    public function testHasRoute()
    {
        $stack = new SimpleRouteStack();
        $this->assertEquals(false, $stack->hasRoute('foo'));

        $stack->addRoute('foo', new TestAsset\DummyRoute());
        $this->assertEquals(true, $stack->hasRoute('foo'));
    }
}
