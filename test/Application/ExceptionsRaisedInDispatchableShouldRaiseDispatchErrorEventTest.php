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

class ExceptionsRaisedInDispatchableShouldRaiseDispatchErrorEventTest extends TestCase
{
    use BadControllerTrait;

    /**
     * @group error-handling
     */
    public function testExceptionsRaisedInDispatchableShouldRaiseDispatchErrorEvent()
    {
        $application = $this->prepareApplication();

        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $exception = $e->getParam('exception');
            $this->assertInstanceOf('Exception', $exception);
            $response = new Response();
            $response->getBody()->write($exception->getMessage());
            $e->setResponse($response);
            return $response;
        });

        $request = new ServerRequest([], [], 'http://example.local/bad', 'GET', 'php://memory');
        $response = $application->handle($request);
        $this->assertContains('Raised an exception', $response->getBody()->__toString());
    }
}
