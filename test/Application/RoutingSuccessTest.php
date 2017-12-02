<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Application;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\ServerRequest;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteResult;

class RoutingSuccessTest extends TestCase
{
    use PathControllerTrait;

    public function testRoutingIsExcecutedDuringRun()
    {
        $application = $this->prepareApplication();

        $log = [];

        $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, function ($e) use (&$log) {
            $result = $e->getRequest()->getAttribute(RouteResult::class);
            $this->assertInstanceOf(RouteResult::class, $result, 'Did not receive expected route match');
            $log['route-result'] = $result;
        }, -100);

        $request = new ServerRequest([], [], 'http://example.local/path', 'GET', 'php://memory');
        $resultResponse = $application->handle($request);
        $this->assertArrayHasKey('route-result', $log);
        $this->assertInstanceOf(RouteResult::class, $log['route-result']);
    }
}
