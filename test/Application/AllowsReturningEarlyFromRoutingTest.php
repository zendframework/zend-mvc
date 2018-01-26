<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Application;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Mvc\MvcEvent;

/**
 * @coversNothing
 */
class AllowsReturningEarlyFromRoutingTest extends TestCase
{
    use PathControllerTrait;

    public function testAllowsReturningEarlyFromRouting()
    {
        $application = $this->prepareApplication();

        $response = new Response();

        $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, function ($e) use ($response) {
            return $response;
        });

        $request = new ServerRequest([], [], 'http://example.local/path', 'GET', 'php://memory');
        $resultResponse = $application->handle($request);
        $this->assertSame($response, $resultResponse);
    }
}
