<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Application;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;

use function ob_get_clean;
use function ob_start;

/**
 * @coversNothing
 */
class InitializationIntegrationTest extends TestCase
{
    public function testDefaultInitializationWorkflow()
    {
        $appConfig = [
            'modules'                 => [
                'Zend\Router',
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

        ob_start();
        $application->run();
        $content = ob_get_clean();

        $response = $application->getResponse();
        $this->assertStringContainsString('Application\\Controller\\PathController', $response->getContent());
        $this->assertStringContainsString('Application\\Controller\\PathController', $content);
        $this->assertStringContainsString(MvcEvent::EVENT_DISPATCH, $response->toString());
    }
}
