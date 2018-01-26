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

/**
 * @coversNothing
 */
class ControllerIsDispatchedTest extends TestCase
{
    use PathControllerTrait;

    public function testControllerIsDispatchedDuringRun()
    {
        $application = $this->prepareApplication();

        $request = new ServerRequest([], [], 'http://example.local/path', 'GET', 'php://memory');
        $response = $application->handle($request);
        $this->assertContains('PathController', $response->getBody()->__toString());
        $this->assertContains(MvcEvent::EVENT_DISPATCH, $response->getBody()->__toString());
    }
}
