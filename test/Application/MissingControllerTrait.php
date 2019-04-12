<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Application;

use Zend\Http\PhpEnvironment\Request;
use Zend\Http\PhpEnvironment\Response;
use Zend\Mvc\ConfigProvider;
use Zend\Router;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use ZendTest\Mvc\TestAsset;

trait MissingControllerTrait
{
    public function prepareApplication()
    {
        $config = [
            'router' => [
                'routes' => [
                    'path' => [
                        'type'    => Router\Http\Literal::class,
                        'options' => [
                            'route'    => '/bad',
                            'defaults' => [
                                'controller' => 'bad',
                                'action'     => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $serviceConfig = ArrayUtils::merge(
            (new ConfigProvider())->getDependencies(),
            (new Router\ConfigProvider())->getDependencyConfig()
        );

        $serviceConfig = ArrayUtils::merge(
            $serviceConfig,
            [
                'factories'  => [
                    'Router' => function ($services) {
                        return $services->get('HttpRouter');
                    },
                ],
                'invokables' => [
                    'Request'              => Request::class,
                    'Response'             => Response::class,
                    'ViewManager'          => TestAsset\MockViewManager::class,
                    'SendResponseListener' => TestAsset\MockSendResponseListener::class,
                    'BootstrapListener'    => TestAsset\StubBootstrapListener::class,
                ],
                'services'   => [
                    'config'            => $config,
                    'ApplicationConfig' => [
                        'modules'                 => [],
                        'module_listener_options' => [
                            'config_cache_enabled' => false,
                            'cache_dir'            => 'data/cache',
                            'module_paths'         => [],
                        ],
                    ],
                ],
            ]
        );
        $services      = new ServiceManager($serviceConfig);
        $application   = $services->get('Application');

        $request = $services->get('Request');
        $request->setUri('http://example.local/bad');

        $application->bootstrap();
        return $application;
    }
}
