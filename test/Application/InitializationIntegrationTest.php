<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Application;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use ZendTest\Mvc\TestAsset;

class InitializationIntegrationTest extends TestCase
{
    public function testDefaultInitializationWorkflow()
    {
        $appConfig = [
            'modules' => [
                'Application',
            ],
            'module_listener_options' => [
                'module_paths' => [
                    __DIR__ . '/TestAsset/modules',
                ],
            ],
        ];

        $application = Application::init($appConfig);

        $request = $application->getRequest();
        $request->setUri('http://example.local/path');
        $request->setRequestUri('/path');

        $application->run();
        $response = $application->getResponse();
        $this->assertContains('Application\\Controller\\PathController', $response->getContent());
        $this->assertContains(MvcEvent::EVENT_DISPATCH, $response->toString());
    }
}
