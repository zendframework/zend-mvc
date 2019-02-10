<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\EventManager\EventManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Application;
use Zend\Mvc\Bootstrapper\BootstrapperInterface;
use Zend\Mvc\Container\ApplicationFactory;
use Zend\Router\Http\TreeRouteStack;
use ZendTest\Mvc\ContainerTrait;

/**
 * @covers \Zend\Mvc\Container\ApplicationFactory
 */
class ApplicationFactoryTest extends TestCase
{
    use ContainerTrait;

    public function testCreatesApplicationWithDependenciesInjected()
    {
        $containerMock = $this->mockContainerInterface();

        $events      = new EventManager();
        $request     = new Request();
        $response    = new Response();
        $initializer = $this->prophesize(BootstrapperInterface::class)
            ->reveal();

        $this->injectServiceInContainer(
            $containerMock,
            BootstrapperInterface::class,
            $initializer
        );
        $this->injectServiceInContainer(
            $containerMock,
            'EventManager',
            $events
        );
        $this->injectServiceInContainer($containerMock, 'Request', $request);
        $this->injectServiceInContainer($containerMock, 'Response', $response);
        // Not doing assertions on this one. Requested from inside application constructor for now.
        // To be dropped from Applicaton and moved to route listener where it belongs.
        $this->injectServiceInContainer($containerMock, 'Router', new TreeRouteStack());

        /** @var ContainerInterface $container */
        $container = $containerMock->reveal();

        $application = (new ApplicationFactory())->__invoke($container);

        $this->assertInstanceOf(Application::class, $application);
        $this->assertSame($events, $application->getEventManager());
        $this->assertSame($request, $application->getRequest());
        $this->assertSame($response, $application->getResponse());
    }
}
