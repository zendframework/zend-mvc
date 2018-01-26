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
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;

/**
 * @coversNothing
 */
class InvalidControllerTypeShouldTrigerDispatchErrorTest extends TestCase
{
    use InvalidControllerTypeTrait;

    /**
     * @group error-handling
     */
    public function testInvalidControllerTypeShouldTriggerDispatchError()
    {
        $application = $this->prepareApplication();

        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) {
            $error      = $e->getError();
            $controller = $e->getController();
            $class      = $e->getControllerClass();
            $response = new Response();
            $response->getBody()->write("Code: " . $error . '; Controller: ' . $controller . '; Class: ' . $class);
            return $response;
        });

        $request = new ServerRequest([], [], 'http://example.local/bad', 'GET', 'php://memory');
        $response = $application->handle($request);
        $this->assertContains(Application::ERROR_CONTROLLER_INVALID, $response->getBody()->__toString());
        $this->assertContains('bad', $response->getBody()->__toString());
    }
}
