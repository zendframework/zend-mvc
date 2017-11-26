<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionObject;
use stdClass;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\Controller\Dispatchable;
use Zend\Mvc\Controller\Plugin\Url;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteResult;
use ZendTest\Mvc\Controller\TestAsset\RestfulContentTypeTestController;
use ZendTest\Mvc\Controller\TestAsset\RestfulMethodNotAllowedTestController;
use ZendTest\Mvc\Controller\TestAsset\RestfulTestController;

class RestfulControllerTest extends TestCase
{
    public $controller;
    public $emptyController;
    /**
     * @var ServerRequest
     */
    public $request;
    public $event;
    public $sharedEvents;
    public $events;

    public function setUp()
    {
        $this->controller      = new RestfulTestController();
        $this->emptyController = new RestfulMethodNotAllowedTestController();
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');
        $routeResult = RouteResult::fromRouteMatch(['controller' => 'controller-restful']);
        $this->request = $request->withAttribute(RouteResult::class, $routeResult);
        $this->event = new MvcEvent();
        $this->controller->setEvent($this->event);
        $this->emptyController->setEvent($this->event);

        $this->sharedEvents = new SharedEventManager();
        $this->events       = $this->createEventManager($this->sharedEvents);
        $this->controller->setEventManager($this->events);
    }

    public function requestWithMatchedParams(ServerRequest $request, array $params)
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $routeResult = $routeResult->withMatchedParams(\array_merge($routeResult->getMatchedParams(), $params));
        foreach ($params as $name => $param) {
            $request = $request->withAttribute($name, $param);
        }
        return $request->withAttribute(RouteResult::class, $routeResult);
    }

    /**
     * @param SharedEventManager
     * @return EventManager
     */
    protected function createEventManager(SharedEventManagerInterface $sharedManager)
    {
        return new EventManager($sharedManager);
    }

    public function testDispatchInvokesListWhenNoActionPresentAndNoIdentifierOnGet()
    {
        $entities = [
            new stdClass,
            new stdClass,
            new stdClass,
        ];
        $this->controller->entities = $entities;
        $result = $this->controller->dispatch($this->request);
        $this->assertArrayHasKey('entities', $result);
        $this->assertEquals($entities, $result['entities']);
        $this->assertEquals(
            'getList',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesGetMethodWhenNoActionPresentAndIdentifierPresentOnGet()
    {
        $entity = new stdClass;
        $this->controller->entity = $entity;
        $request = $this->requestWithMatchedParams($this->request, ['id' => 1]);
        $result = $this->controller->dispatch($request);
        $this->assertArrayHasKey('entity', $result);
        $this->assertEquals($entity, $result['entity']);
        $this->assertEquals(
            'get',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesCreateMethodWhenNoActionPresentAndPostInvoked()
    {
        $entity = ['id' => 1, 'name' => __FUNCTION__];
        $request = $this->request->withMethod('POST');
        $request = $request->withParsedBody($entity);
        $result = $this->controller->dispatch($request);
        $this->assertArrayHasKey('entity', $result);
        $this->assertEquals($entity, $result['entity']);
        $this->assertEquals(
            'create',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testCanReceiveStringAsRequestContent()
    {
        $string = "any content";
        $request = $this->request->withMethod('PUT');
        $request->getBody()->write($string);
        $request = $this->requestWithMatchedParams($request, ['id' => 1]);

        $controller = new RestfulContentTypeTestController();
        $controller->setEvent($this->event);
        $result = $controller->dispatch($request);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals($string, $result['data']);
        $this->assertEquals(
            'update',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesUpdateMethodWhenNoActionPresentAndPutInvokedWithIdentifier()
    {
        $entity = ['name' => __FUNCTION__];
        $string = http_build_query($entity);
        $request = $this->request->withMethod('PUT');
        $request->getBody()->write($string);
        $request = $this->requestWithMatchedParams($request, ['id' => 1]);
        $result = $this->controller->dispatch($request);
        $this->assertArrayHasKey('entity', $result);
        $test = $result['entity'];
        $this->assertArrayHasKey('id', $test);
        $this->assertEquals(1, $test['id']);
        $this->assertArrayHasKey('name', $test);
        $this->assertEquals(__FUNCTION__, $test['name']);
        $this->assertEquals(
            'update',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesReplaceListMethodWhenNoActionPresentAndPutInvokedWithoutIdentifier()
    {
        $entities = [
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
        ];
        $string = http_build_query($entities);
        $request = $this->request->withMethod('PUT');
        $request->getBody()->write($string);
        $result = $this->controller->dispatch($request);
        $this->assertEquals($entities, $result);
        $this->assertEquals(
            'replaceList',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesPatchListMethodWhenNoActionPresentAndPatchInvokedWithoutIdentifier()
    {
        $entities = [
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
        ];
        $string = http_build_query($entities);
        $request = $this->request->withMethod('PATCH');
        $request->getBody()->write($string);
        $result = $this->controller->dispatch($request);
        $this->assertEquals($entities, $result);
        $this->assertEquals(
            'patchList',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesDeleteMethodWhenNoActionPresentAndDeleteInvokedWithIdentifier()
    {
        $entity = ['id' => 1, 'name' => __FUNCTION__];
        $this->controller->entity = $entity;
        $request = $this->request->withMethod('DELETE');
        $request = $this->requestWithMatchedParams($request, ['id' => 1]);
        $result = $this->controller->dispatch($request);
        $this->assertEquals([], $result);
        $this->assertEquals([], $this->controller->entity);
        $this->assertEquals(
            'delete',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesDeleteListMethodWhenNoActionPresentAndDeleteInvokedWithoutIdentifier()
    {
        $entities = [
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
            ['id' => uniqid(), 'name' => __FUNCTION__],
        ];

        $this->controller->entity = $entities;

        $string = http_build_query($entities);
        $request = $this->request->withMethod('DELETE');
        $request->getBody()->write($string);
        $result = $this->controller->dispatch($request);
        $this->assertEmpty($this->controller->entity);
        $this->assertEquals(204, $result->getStatusCode());
        $this->assertTrue($result->hasHeader('X-Deleted'));
        $this->assertEquals(
            'deleteList',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesOptionsMethodWhenNoActionPresentAndOptionsInvoked()
    {
        $request = $this->request->withMethod('OPTIONS');
        $result = $this->controller->dispatch($request);
        $this->assertEquals(
            'options',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
        $this->assertTrue($result->hasHeader('Allow'));
        $allow = $result->getHeader('Allow')[0];
        $expected = explode(', ', 'GET, POST, PUT, DELETE, PATCH, HEAD, TRACE');
        sort($expected);
        $test     = explode(', ', $allow);
        sort($test);
        $this->assertEquals($expected, $test);
    }

    public function testDispatchInvokesPatchMethodWhenNoActionPresentAndPatchInvokedWithIdentifier()
    {
        $entity = new stdClass;
        $entity->name = 'foo';
        $entity->type = 'standard';
        $this->controller->entity = $entity;
        $entity = ['name' => __FUNCTION__];
        $string = http_build_query($entity);
        $request = $this->request->withMethod('PATCH');
        $request->getBody()->write($string);
        $request = $this->requestWithMatchedParams($request, ['id' => 1]);
        $result = $this->controller->dispatch($request);
        $this->assertArrayHasKey('entity', $result);
        $test = $result['entity'];
        $this->assertArrayHasKey('id', $test);
        $this->assertEquals(1, $test['id']);
        $this->assertArrayHasKey('name', $test);
        $this->assertEquals(__FUNCTION__, $test['name']);
        $this->assertArrayHasKey('type', $test);
        $this->assertEquals('standard', $test['type']);
        $this->assertEquals(
            'patch',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    /**
     * @group 7086
     */
    public function testOnDispatchHonorsStatusCodeWithHeadMethod()
    {
        $response = new Response();
        $response = $response
            ->withStatus(418)
            ->withAddedHeader('Custom-Header', 'Header Value');

        $this->controller->headResponse = $response;
        $request = $this->request->withMethod('HEAD');
        $request = $this->requestWithMatchedParams($request, ['id' => 1]);
        $result = $this->controller->dispatch($request);

        $this->assertEquals(418, $result->getStatusCode());
        $this->assertEquals('', $result->getBody()->__toString());
        $this->assertEquals(
            'head',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
        $this->assertEquals('Header Value', $result->getHeader('Custom-Header')[0] ?? 'No header set');
    }

    public function testDispatchInvokesHeadMethodWhenNoActionPresentAndHeadInvokedWithoutIdentifier()
    {
        $entities = [
            new stdClass,
            new stdClass,
            new stdClass,
        ];
        $this->controller->entities = $entities;
        $request = $this->request->withMethod('HEAD');
        $result = $this->controller->dispatch($request);
        $content = $result->getBody()->__toString();
        $this->assertEquals('', $content);
        $this->assertEquals(
            'head',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testDispatchInvokesHeadMethodWhenNoActionPresentAndHeadInvokedWithIdentifier()
    {
        $entity = new stdClass;
        $this->controller->entity = $entity;
        $request = $this->request->withMethod('HEAD');
        $request = $this->requestWithMatchedParams($request, ['id' => 1]);
        $result = $this->controller->dispatch($request);
        $content = $result->getBody()->__toString();
        $this->assertEquals('', $content);
        $this->assertEquals(
            'head',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );

        $this->assertTrue($result->hasHeader('X-ZF2-Id'));
        $this->assertEquals(1, $result->getHeader('X-ZF2-Id')[0]);
    }

    public function testAllowsRegisteringCustomHttpMethodsWithHandlers()
    {
        $this->controller->addHttpMethodHandler('DESCRIBE', [$this->controller, 'describe']);
        $request = $this->request->withMethod('DESCRIBE');
        $result = $this->controller->dispatch($request);
        $this->assertArrayHasKey('description', $result);
        $this->assertContains('::describe', $result['description']);
    }

    public function testDispatchCallsActionMethodBasedOnNormalizingAction()
    {
        $request = $this->requestWithMatchedParams($this->request, ['action' => 'test.some-strangely_separated.words']);
        $result = $this->controller->dispatch($request);
        $this->assertArrayHasKey('content', $result);
        $this->assertContains('Test Some Strangely Separated Words', $result['content']);
    }

    public function testDispatchCallsNotFoundActionWhenActionPassedThatCannotBeMatched()
    {
        $request = $this->requestWithMatchedParams($this->request, ['action' => 'test-some-made-up-action']);
        $result   = $this->controller->dispatch($request);
        $response = $this->controller->getResponse();
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertArrayHasKey('content', $result);
        $this->assertContains('Page not found', $result['content']);
    }

    public function testShortCircuitsBeforeActionIfPreDispatchReturnsAResponse()
    {
        $response = new Response();
        $response->getBody()->write('short circuited!');
        $this->controller->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, 10);
        $result = $this->controller->dispatch($this->request);
        $this->assertSame($response, $result);
    }

    public function testPostDispatchEventAllowsReplacingResponse()
    {
        $response = new Response();
        $response->getBody()->write('short circuited!');
        $this->controller->getEventManager()->attach(MvcEvent::EVENT_DISPATCH, function ($e) use ($response) {
            return $response;
        }, -10);
        $result = $this->controller->dispatch($this->request);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnDispatchableInterfaceByDefault()
    {
        $response = new Response();
        $response->getBody()->write('short circuited!');
        $this->sharedEvents->attach(
            Dispatchable::class,
            MvcEvent::EVENT_DISPATCH,
            function ($e) use ($response) {
                return $response;
            },
            10
        );
        $result = $this->controller->dispatch($this->request);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnRestfulControllerClassByDefault()
    {
        $response = new Response();
        $response->getBody()->write('short circuited!');
        $this->sharedEvents->attach(
            AbstractRestfulController::class,
            MvcEvent::EVENT_DISPATCH,
            function ($e) use ($response) {
                return $response;
            },
            10
        );
        $result = $this->controller->dispatch($this->request);
        $this->assertSame($response, $result);
    }

    public function testEventManagerListensOnClassNameByDefault()
    {
        $response = new Response();
        $response->getBody()->write('short circuited!');
        $this->sharedEvents->attach(
            get_class($this->controller),
            MvcEvent::EVENT_DISPATCH,
            function ($e) use ($response) {
                return $response;
            },
            10
        );
        $result = $this->controller->dispatch($this->request);
        $this->assertSame($response, $result);
    }

    public function testDispatchInjectsEventIntoController()
    {
        $this->controller->dispatch($this->request);
        $event = $this->controller->getEvent();
        $this->assertNotNull($event);
        $this->assertSame($this->event, $event);
    }

    public function testControllerIsEventAware()
    {
        $this->assertInstanceOf(InjectApplicationEventInterface::class, $this->controller);
    }

    public function testControllerIsPluggable()
    {
        $this->assertTrue(method_exists($this->controller, 'plugin'));
    }

    public function testMethodOverloadingShouldReturnPluginWhenFound()
    {
        $plugin = $this->controller->url();
        $this->assertInstanceOf(Url::class, $plugin);
    }

    public function testMethodOverloadingShouldInvokePluginAsFunctorIfPossible()
    {
        $model = $this->event->getViewModel();
        $this->controller->layout('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testParsingDataAsJsonWillReturnAsArray()
    {
        $request = $this->request->withMethod('POST')
            ->withAddedHeader('Content-type', 'application/json');
        $request->getBody()->write('{"foo":"bar"}');

        $result = $this->controller->dispatch($request);
        $this->assertInternalType('array', $result);
        $this->assertEquals(['entity' => ['foo' => 'bar']], $result);
    }

    public function matchingContentTypes()
    {
        return [
            'exact-first' => ['application/hal+json'],
            'exact-second' => ['application/json'],
            'with-charset' => ['application/json; charset=utf-8'],
            'with-whitespace' => ['application/json '],
        ];
    }

    /**
     * @dataProvider matchingContentTypes
     */
    public function testRequestingContentTypeReturnsTrueForValidMatches($contentType)
    {
        $request = $this->request->withAddedHeader('Content-Type', $contentType);
        $this->assertTrue($this->controller->requestHasContentType(
            $request,
            RestfulTestController::CONTENT_TYPE_JSON
        ));
    }

    public function nonMatchingContentTypes()
    {
        return [
            'specific-type' => ['application/xml'],
            'generic-type' => ['text/json'],
        ];
    }

    /**
     * @dataProvider nonMatchingContentTypes
     */
    public function testRequestingContentTypeReturnsFalseForInvalidMatches($contentType)
    {
        $request = $this->request->withAddedHeader('Content-Type', $contentType);
        $this->assertFalse($this->controller->requestHasContentType(
            $request,
            RestfulTestController::CONTENT_TYPE_JSON
        ));
    }

    public function testDispatchWithUnrecognizedMethodReturns405Response()
    {
        $request = $this->request->withMethod('PROPFIND');
        $result = $this->controller->dispatch($request);
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(405, $result->getStatusCode());
    }

    public function testDispatchInvokesGetMethodWhenNoActionPresentAndZeroIdentifierPresentOnGet()
    {
        $entity = new stdClass;
        $this->controller->entity = $entity;
        $request = $this->requestWithMatchedParams($this->request, ['id' => 0]);
        $result = $this->controller->dispatch($request);
        $this->assertArrayHasKey('entity', $result);
        $this->assertEquals($entity, $result['entity']);
        $this->assertEquals(
            'get',
            $this->controller->getRequest()->getAttribute(RouteResult::class)->getMatchedParams()['action'] ?? null
        );
    }

    public function testIdentifierNameDefaultsToId()
    {
        $this->assertEquals('id', $this->controller->getIdentifierName());
    }

    public function testCanSetIdentifierName()
    {
        $this->controller->setIdentifierName('name');
        $this->assertEquals('name', $this->controller->getIdentifierName());
    }

    public function testUsesConfiguredIdentifierNameToGetIdentifier()
    {
        $r = new ReflectionObject($this->controller);
        $getIdentifier = $r->getMethod('getIdentifier');
        $getIdentifier->setAccessible(true);

        $this->controller->setIdentifierName('name');

        $request = $this->request->withAttribute('name', 'foo');
        $result = $getIdentifier->invoke($this->controller, $request);
        $this->assertEquals('foo', $result);

        $request = $this->request->withAttribute('name', false);
        $request = $this->request->withQueryParams(['name' => 'bar']);
        $result = $getIdentifier->invoke($this->controller, $request);
        $this->assertEquals('bar', $result);
    }

    /**
     * @dataProvider providerNotImplementedMethodSets504HttpCodeProvider
     */
    public function testNotImplementedMethodSets504HttpCode($method, $content, array $routeParams)
    {
        $request = $this->request->withMethod($method);

        if ($content) {
            $request->getBody()->write($content);
        }

        $request = $this->requestWithMatchedParams($request, $routeParams);

        $result   = $this->emptyController->dispatch($request);
        $response = $this->emptyController->getResponse();

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('Method Not Allowed', $response->getReasonPhrase());
    }

    public function providerNotImplementedMethodSets504HttpCodeProvider()
    {
        return [
            ['DELETE',  [],                             ['id' => 1]], // AbstractRestfulController::delete()
            ['DELETE',  [],                             []],          // AbstractRestfulController::deleteList()
            ['GET',     [],                             ['id' => 1]], // AbstractRestfulController::get()
            ['GET',     [],                             []],          // AbstractRestfulController::getList()
            ['HEAD',    [],                             ['id' => 1]], // AbstractRestfulController::head()
            ['HEAD',    [],                             []],          // AbstractRestfulController::head()
            ['OPTIONS', [],                             []],          // AbstractRestfulController::options()
            ['PATCH',   http_build_query(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::patch()
            ['PATCH',   json_encode(['foo' => 1]),      ['id' => 1]], // AbstractRestfulController::patch()
            ['PATCH',   http_build_query(['foo' => 1]), []],          // AbstractRestfulController::patchList()
            ['PATCH',   json_encode(['foo' => 1]),      []],          // AbstractRestfulController::patchList()
            ['POST',    http_build_query(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::update()
            ['POST',    json_encode(['foo' => 1]),      ['id' => 1]], // AbstractRestfulController::update()
            ['POST',    http_build_query(['foo' => 1]), []],          // AbstractRestfulController::create()
            ['POST',    json_encode(['foo' => 1]),      []],          // AbstractRestfulController::create()
            ['PUT',     http_build_query(['foo' => 1]), ['id' => 1]], // AbstractRestfulController::update()
            ['PUT',     json_encode(['foo' => 1]),      ['id' => 1]], // AbstractRestfulController::update()
            ['PUT',     http_build_query(['foo' => 1]), []],          // AbstractRestfulController::replaceList()
            ['PUT',     json_encode(['foo' => 1]),      []],          // AbstractRestfulController::replaceList()
        ];
    }
}
