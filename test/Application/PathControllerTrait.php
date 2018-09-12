<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\Application;

use ReflectionProperty;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\Application;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\Mvc\Service\ServiceListenerFactory;
use Zend\Router;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use ZendTest\Mvc\TestAsset;

trait PathControllerTrait
{
    public function prepareApplication()
    {
        $config = [
            'router' => [
                'routes' => [
                    'path' => [
                        'type' => Router\Http\Literal::class,
                        'options' => [
                            'route' => '/path',
                            'defaults' => [
                                'controller' => 'path',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $serviceListener = new ServiceListenerFactory();
        $r = new ReflectionProperty($serviceListener, 'defaultServiceConfig');
        $r->setAccessible(true);
        $serviceConfig = $r->getValue($serviceListener);

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            (new Router\ConfigProvider())->getDependencyConfig()
        );

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            [
                'factories' => [
                    'ControllerManager' => function ($services) {
                        return new ControllerManager($services, ['factories' => [
                            'path' => function () {
                                return new TestAsset\PathController();
                            },
                        ]]);
                    },
                    'Router' => function ($services) {
                        return $services->get('HttpRouter');
                    },
                ],
                'invokables' => [
                    'ViewManager'          => TestAsset\MockViewManager::class,
                    'SendResponseListener' => TestAsset\MockSendResponseListener::class,
                    'BootstrapListener'    => TestAsset\StubBootstrapListener::class,
                ],
                'services' => [
                    'config' => $config,
                    'ApplicationConfig' => [
                        'modules'                 => [
                            'Zend\Router',
                        ],
                        'module_listener_options' => [
                            'config_cache_enabled' => false,
                            'cache_dir'            => 'data/cache',
                            'module_paths'         => [],
                        ],
                    ],
                ],
            ]
        );
        $services = new ServiceManager();
        (new ServiceManagerConfig($serviceConfig))->configureServiceManager($services);
        $application = $services->get('Application');

        $request = $services->get('Request');
        $request->setUri('http://example.local/path');

        $application->bootstrap();
        return $application;
    }
}
