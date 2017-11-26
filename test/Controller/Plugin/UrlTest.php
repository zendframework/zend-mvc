<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller\Plugin;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\Mvc\Controller\Plugin\Url as UrlPlugin;
use Zend\Mvc\Exception\DomainException;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\MvcEvent;
use Zend\Router\Route\Literal as LiteralRoute;
use Zend\Router\Route\Segment as SegmentRoute;
use Zend\Router\Route\Segment;
use Zend\Router\RouteResult;
use Zend\Router\SimpleRouteStack;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

class UrlTest extends TestCase
{
    public function setUp()
    {
        $router = new SimpleRouteStack;
        $router->addRoute('home', LiteralRoute::factory([
            'route'    => '/',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $router->addRoute('default', [
            'type' => Segment::class,
            'options' => [
                'route' => '/:controller[/:action]',
            ]
        ]);
        $this->router = $router;

        $event = new MvcEvent();
        $event->setRouter($router);

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('url');
    }

    public function testPluginCanGenerateUrlWhenProperlyConfigured()
    {
        $url = $this->plugin->fromRoute('home');
        $this->assertEquals('/', $url);
    }

    public function testModel()
    {
        $it = new \ArrayIterator(['controller' => 'ctrl', 'action' => 'act']);

        $url = $this->plugin->fromRoute('default', $it);
        $this->assertEquals('/ctrl/act', $url);
    }

    public function testPluginWithoutControllerRaisesDomainException()
    {
        $plugin = new UrlPlugin();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('requires a controller');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutControllerEventRaisesDomainException()
    {
        $controller = new SampleController();
        $plugin     = $controller->plugin('url');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouterInEventRaisesDomainException()
    {
        $controller = new SampleController();
        $event      = new MvcEvent();
        $controller->setEvent($event);
        $plugin = $controller->plugin('url');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouteMatchesInEventRaisesExceptionWhenNoRouteProvided()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('RouteResult');
        $url = $this->plugin->fromRoute();
    }

    public function testPluginWithRouteMatchesReturningNoMatchedRouteNameRaisesExceptionWhenNoRouteProvided()
    {
        $event = $this->controller->getEvent();
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');
        $request = $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch([]));
        $event->setRequest($request);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('matched');
        $url = $this->plugin->fromRoute();
    }

    public function testPassingNoArgumentsWithValidRouteMatchGeneratesUrl()
    {
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');
        $request = $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch([], 'home'));
        $this->controller->getEvent()->setRequest($request);
        $url = $this->plugin->fromRoute();
        $this->assertEquals('/', $url);
    }

    public function testCanReuseMatchedParameters()
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');
        $request = $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch(
            ['controller' => 'foo'],
            'replace'
        ));
        $this->controller->getEvent()->setRequest($request);
        $url = $this->plugin->fromRoute('replace', ['action' => 'bar'], [], true);
        $this->assertEquals('/foo/bar', $url);
    }

    public function testCanPassBooleanValueForThirdArgumentToAllowReusingRouteMatches()
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');
        $request = $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch(
            ['controller' => 'foo'],
            'replace'
        ));
        $this->controller->getEvent()->setRequest($request);
        $url = $this->plugin->fromRoute('replace', ['action' => 'bar'], true);
        $this->assertEquals('/foo/bar', $url);
    }
}
