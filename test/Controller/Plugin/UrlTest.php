<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\Plugin;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Controller\Plugin\Url as UrlPlugin;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\Literal as LiteralRoute;
use Zend\Mvc\Router\Http\Segment as SegmentRoute;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Router\SimpleRouteStack;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

class UrlTest extends TestCase
{
    public function setUp()
    {
        $router = new SimpleRouteStack;
        $router->addRoute('home', LiteralRoute::factory([
            'route'    => '/',
            'defaults' => [
                'controller' => 'ZendTest\Mvc\Controller\TestAsset\SampleController',
            ],
        ]));
        $router->addRoute('default', [
            'type' => 'Zend\Mvc\Router\Http\Segment',
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
        $this->setExpectedException('Zend\Mvc\Exception\DomainException', 'requires a controller');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutControllerEventRaisesDomainException()
    {
        $controller = new SampleController();
        $plugin     = $controller->plugin('url');
        $this->setExpectedException('Zend\Mvc\Exception\DomainException', 'event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouterInEventRaisesDomainException()
    {
        $controller = new SampleController();
        $event      = new MvcEvent();
        $controller->setEvent($event);
        $plugin = $controller->plugin('url');
        $this->setExpectedException('Zend\Mvc\Exception\DomainException', 'event compose a router');
        $plugin->fromRoute('home');
    }

    public function testPluginWithoutRouteMatchesInEventRaisesExceptionWhenNoRouteProvided()
    {
        $this->setExpectedException('Zend\Mvc\Exception\RuntimeException', 'RouteMatch');
        $url = $this->plugin->fromRoute();
    }

    public function testPluginWithRouteMatchesReturningNoMatchedRouteNameRaisesExceptionWhenNoRouteProvided()
    {
        $event = $this->controller->getEvent();
        $event->setRouteMatch(new RouteMatch([]));
        $this->setExpectedException('Zend\Mvc\Exception\RuntimeException', 'matched');
        $url = $this->plugin->fromRoute();
    }

    public function testPassingNoArgumentsWithValidRouteMatchGeneratesUrl()
    {
        $routeMatch = new RouteMatch([]);
        $routeMatch->setMatchedRouteName('home');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $url = $this->plugin->fromRoute();
        $this->assertEquals('/', $url);
    }

    public function testCanReuseMatchedParameters()
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => 'ZendTest\Mvc\Controller\TestAsset\SampleController',
            ],
        ]));
        $routeMatch = new RouteMatch([
            'controller' => 'foo',
        ]);
        $routeMatch->setMatchedRouteName('replace');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $url = $this->plugin->fromRoute('replace', ['action' => 'bar'], [], true);
        $this->assertEquals('/foo/bar', $url);
    }

    public function testCanPassBooleanValueForThirdArgumentToAllowReusingRouteMatches()
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => 'ZendTest\Mvc\Controller\TestAsset\SampleController',
            ],
        ]));
        $routeMatch = new RouteMatch([
            'controller' => 'foo',
        ]);
        $routeMatch->setMatchedRouteName('replace');
        $this->controller->getEvent()->setRouteMatch($routeMatch);
        $url = $this->plugin->fromRoute('replace', ['action' => 'bar'], true);
        $this->assertEquals('/foo/bar', $url);
    }

    public function testRemovesModuleRouteListenerParamsWhenReusingMatchedParameters()
    {
        $router = new \Zend\Mvc\Router\Http\TreeRouteStack;
        $router->addRoute('default', [
            'type' => 'Zend\Mvc\Router\Http\Segment',
            'options' => [
                'route'    => '/:controller/:action',
                'defaults' => [
                    ModuleRouteListener::MODULE_NAMESPACE => 'ZendTest\Mvc\Controller\TestAsset',
                    'controller' => 'SampleController',
                    'action'     => 'Dash'
                ]
            ],
            'child_routes' => [
                'wildcard' => [
                    'type'    => 'Zend\Mvc\Router\Http\Wildcard',
                    'options' => [
                        'param_delimiter'     => '=',
                        'key_value_delimiter' => '%'
                    ]
                ]
            ]
        ]);

        $routeMatch = new RouteMatch([
            ModuleRouteListener::MODULE_NAMESPACE => 'ZendTest\Mvc\Controller\TestAsset',
            'controller' => 'Rainbow'
        ]);
        $routeMatch->setMatchedRouteName('default/wildcard');

        $event = new MvcEvent();
        $event->setRouter($router)
              ->setRouteMatch($routeMatch);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->onRoute($event);

        $controller = new SampleController();
        $controller->setEvent($event);
        $url = $controller->plugin('url')->fromRoute('default/wildcard', ['Twenty' => 'Cooler'], true);

        $this->assertEquals('/Rainbow/Dash=Twenty%Cooler', $url);
    }
}
