<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\View;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\EventManager\EventManager;
use Zend\EventManager\Test\EventListenerIntrospectionTrait;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\View\Http\InjectTemplateListener;
use Zend\Router\RouteResult;
use Zend\View\Model\ViewModel;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

class InjectTemplateListenerTest extends TestCase
{
    use EventListenerIntrospectionTrait;

    /**
     * @var MvcEvent
     */
    private $event;

    public function setUp()
    {
        $this->listener   = new InjectTemplateListener();
        $this->event      = new MvcEvent();
        $request  = new ServerRequest([], [], null, 'GET', 'php://memory');
        $this->event->setRequest($request);
    }

    public function testSetsTemplateBasedOnRouteMatchIfNoTemplateIsSetOnViewModel()
    {
        $result = RouteResult::fromRouteMatch([
            'controller' => 'Foo\Controller\SomewhatController',
            'action' => 'useful',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('foo/somewhat/useful', $model->getTemplate());
    }

    public function testUsesModuleAndControllerOnlyIfNoActionInRouteMatch()
    {
        $result = RouteResult::fromRouteMatch([
            'controller' => 'Foo\Controller\SomewhatController',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('foo/somewhat', $model->getTemplate());
    }

    public function testNormalizesLiteralControllerNameIfNoNamespaceSeparatorPresent()
    {
        $result = RouteResult::fromRouteMatch([
            'controller' => 'SomewhatController',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('somewhat', $model->getTemplate());
    }

    public function testNormalizesNamesToLowercase()
    {
        $result = RouteResult::fromRouteMatch([
            'controller' => 'Somewhat.DerivedController',
            'action' => 'some-UberCool',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );

        $model = new ViewModel();
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('somewhat.derived/some-uber-cool', $model->getTemplate());
    }

    public function testLackOfViewModelInResultBypassesTemplateInjection()
    {
        $this->assertNull($this->listener->injectTemplate($this->event));
        $this->assertNull($this->event->getResult());
    }

    public function testBypassesTemplateInjectionIfResultViewModelAlreadyHasATemplate()
    {
        $result = RouteResult::fromRouteMatch([
            'controller' => 'Foo\Controller\SomewhatController',
            'action' => 'useful',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );

        $model = new ViewModel();
        $model->setTemplate('custom');
        $this->event->setResult($model);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('custom', $model->getTemplate());
    }

    public function testMapsSubNamespaceToSubDirectory()
    {
        $myViewModel  = new ViewModel();
        $myController = new SampleController();
        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('zend-test/mvc/test-asset/sample', $myViewModel->getTemplate());
    }

    public function testControllerMatchedByMapIsInflected()
    {
        $result = RouteResult::fromRouteMatch([
            'controller' => 'MappedNs\SubNs\Controller\Sample',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );
        $myViewModel  = new ViewModel();

        $this->event->setResult($myViewModel);
        $this->listener->injectTemplate($this->event);

        $this->assertEquals('mapped-ns/sub-ns/sample', $myViewModel->getTemplate());

        $myViewModel  = new ViewModel();
        $myController = new SampleController();
        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);

        $this->listener->injectTemplate($this->event);

        $this->assertEquals('zend-test/mvc/test-asset/sample', $myViewModel->getTemplate());
    }

    public function testFullControllerNameMatchIsMapped()
    {
        $this->listener->setControllerMap([
            'Foo\Bar\Controller\IndexController' => 'string-value',
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('string-value', $template);
    }

    public function testOnlyFullNamespaceMatchIsMapped()
    {
        $this->listener->setControllerMap([
            'Foo' => 'foo-matched',
            'Foo\Bar' => 'foo-bar-matched',
        ]);
        $template = $this->listener->mapController('Foo\BarBaz\Controller\IndexController');
        $this->assertEquals('foo-matched/bar-baz/index', $template);
    }

    public function testControllerMapMatchedPrefixReplacedByStringValue()
    {
        $this->listener->setControllerMap([
            'Foo\Bar' => 'string-value',
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('string-value/index', $template);
    }

    public function testControllerMapOnlyFullNamespaceMatches()
    {
        $this->listener->setControllerMap([
            'Foo' => 'foo-matched',
            'Foo\Bar' => 'foo-bar-matched',
        ]);
        $template = $this->listener->mapController('Foo\BarBaz\Controller\IndexController');
        $this->assertEquals('foo-matched/bar-baz/index', $template);
    }

    public function testControllerMapRuleSetToFalseIsIgnored()
    {
        $this->listener->setControllerMap([
            'Foo' => 'foo-matched',
            'Foo\Bar' => false,
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('foo-matched/bar/index', $template);
    }

    public function testControllerMapMoreSpecificRuleMatchesFirst()
    {
        $this->listener->setControllerMap([
            'Foo'     => true,
            'Foo\Bar' => 'bar/baz',
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('bar/baz/index', $template);

        $this->listener->setControllerMap([
            'Foo\Bar' => 'bar/baz',
            'Foo'     => true,
        ]);
        $template = $this->listener->mapController('Foo\Bar\Controller\IndexController');
        $this->assertEquals('bar/baz/index', $template);
    }

    public function testAttachesListenerAtExpectedPriority()
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $this->assertListenerAtPriority(
            [$this->listener, 'injectTemplate'],
            -90,
            MvcEvent::EVENT_DISPATCH,
            $events
        );
    }

    public function testDetachesListeners()
    {
        $events = new EventManager();
        $this->listener->attach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertEquals(1, count($listeners));

        $this->listener->detach($events);
        $listeners = $this->getArrayOfListenersForEvent(MvcEvent::EVENT_DISPATCH, $events);
        $this->assertEquals(0, count($listeners));
    }

    public function testPrefersRouteResultController()
    {
        $this->assertFalse($this->listener->isPreferRouteResultController());
        $this->listener->setPreferRouteResultController(true);

        $result = RouteResult::fromRouteMatch([
            'controller' => 'Some\Other\Service\Namespace\Controller\Sample',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );
        $myViewModel  = new ViewModel();
        $myController = new SampleController();

        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);
        $this->listener->injectTemplate($this->event);

        $this->assertEquals('some/other/service/namespace/sample', $myViewModel->getTemplate());
    }

    public function testPrefersRouteMatchControllerWithRouteMatchAndControllerMap()
    {
        $this->assertFalse($this->listener->isPreferRouteResultController());
        $controllerMap = [
            'Some\Other\Service\Namespace\Controller\Sample' => 'another/sample'
        ];

        $result = RouteResult::fromRouteMatch([
            'prefer_route_result_controller' => true,
            'controller' => 'Some\Other\Service\Namespace\Controller\Sample',
        ]);
        $this->event->setRequest(
            $this->event->getRequest()->withAttribute(RouteResult::class, $result)
        );

        $this->listener->setControllerMap($controllerMap);

        $myViewModel  = new ViewModel();
        $myController = new SampleController();

        $this->event->setTarget($myController);
        $this->event->setResult($myViewModel);
        $this->listener->injectTemplate($this->event);

        $this->assertEquals('another/sample', $myViewModel->getTemplate());
    }
}
