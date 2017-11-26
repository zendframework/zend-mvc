<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller\Plugin;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Mvc\Controller\Plugin\Redirect as RedirectPlugin;
use Zend\Mvc\Exception\DomainException;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\MvcEvent;
use Zend\Router\Route\Literal as LiteralRoute;
use Zend\Router\Route\Segment as SegmentRoute;
use Zend\Router\RouteResult;
use Zend\Router\SimpleRouteStack;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

class RedirectTest extends TestCase
{
    /**
     * @var RedirectPlugin
     */
    public $plugin;

    public function setUp()
    {
        $router = new SimpleRouteStack;
        $router->addRoute('home', LiteralRoute::factory([
            'route'    => '/',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $this->router = $router;

        $event = new MvcEvent();

        $request = new ServerRequest([], [], null, 'GET', 'php://memory');
        $request = $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch([], 'home'));
        $this->request = $request;
        $event->setRequest($request);

        $event->setRouter($router);
        $this->event = $event;

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('redirect');
    }

    public function testPluginCanRedirectToRouteWhenProperlyConfigured()
    {
        $response = $this->plugin->toRoute('home');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertCount(1, $response->getHeader('Location'));
        $this->assertEquals('/', $response->getHeader('Location')[0]);
    }

    public function testPluginCanRedirectToUrlWhenProperlyConfigured()
    {
        $response = $this->plugin->toUrl('/foo');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertCount(1, $response->getHeader('Location'));
        $this->assertEquals('/foo', $response->getHeader('Location')[0]);
    }

    public function testPluginWithoutControllerRaisesDomainException()
    {
        $plugin = new RedirectPlugin();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('requires a controller');
        $plugin->toRoute('home');
    }

    public function testPluginWithoutControllerEventRaisesDomainException()
    {
        $controller = new SampleController();
        $plugin     = $controller->plugin('redirect');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose');
        $plugin->toRoute('home');
    }

    public function testPluginWithoutResponseInEventReturnsNewResponse()
    {
        $this->assertInstanceOf(ResponseInterface::class, $this->plugin->toRoute('home'));
    }

    public function testRedirectToRouteWithoutRouterInEventRaisesDomainException()
    {
        $controller = new SampleController();
        $event      = new MvcEvent();
        $controller->setEvent($event);
        $plugin = $controller->plugin('redirect');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('event compose a router');
        $plugin->toRoute('home');
    }

    public function testPluginWithoutRouteMatchesInEventRaisesExceptionWhenNoRouteProvided()
    {
        $request = $this->request->withoutAttribute(RouteResult::class);
        $this->controller->getEvent()->setRequest($request);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('RouteResult');
        $url = $this->plugin->toRoute();
    }

    public function testPassingNoArgumentsWithValidRouteMatchGeneratesUrl()
    {
        $response = $this->plugin->toRoute();
        $this->assertCount(1, $response->getHeader('Location'));
        $this->assertEquals('/', $response->getHeader('Location')[0]);
    }

    public function testCanReuseMatchedParameters()
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $request = $this->request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch(
            ['controller' => 'foo'],
            'replace'
        ));
        $this->controller->getEvent()->setRequest($request);
        $response = $this->plugin->toRoute('replace', ['action' => 'bar'], [], true);
        $this->assertCount(1, $response->getHeader('Location'));
        $this->assertEquals('/foo/bar', $response->getHeader('Location')[0]);
    }

    public function testCanPassBooleanValueForThirdArgumentToAllowReusingRouteMatches()
    {
        $this->router->addRoute('replace', SegmentRoute::factory([
            'route'    => '/:controller/:action',
            'defaults' => [
                'controller' => SampleController::class,
            ],
        ]));
        $request = $this->request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch(
            ['controller' => 'foo'],
            'replace'
        ));
        $this->controller->getEvent()->setRequest($request);
        $response = $this->plugin->toRoute('replace', ['action' => 'bar'], true);
        $this->assertCount(1, $response->getHeader('Location'));
        $this->assertEquals('/foo/bar', $response->getHeader('Location')[0]);
    }

    public function testPluginCanRefreshToRouteWhenProperlyConfigured()
    {
        $response = $this->plugin->refresh();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertCount(1, $response->getHeader('Location'));
        $this->assertEquals('/', $response->getHeader('Location')[0]);
    }

    public function testPluginCanRedirectToRouteWithNullWhenProperlyConfigured()
    {
        $response = $this->plugin->toRoute();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertCount(1, $response->getHeader('Location'));
        $this->assertEquals('/', $response->getHeader('Location')[0]);
    }
}
