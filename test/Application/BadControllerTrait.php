<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Application;

use Zend\Mvc\Application;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\View\Http\ViewManager;
use Zend\Router;
use ZendTest\Mvc\Controller\TestAsset\BadController;
use ZendTest\Mvc\TestAsset;
use ZendTest\Mvc\TestAsset\ApplicationConfigHelper;

trait BadControllerTrait
{
    public function prepareApplication()
    {
        $config = ApplicationConfigHelper::getConfig([
            'dependencies' => [
                'factories' => [
                    ControllerManager::class => function ($services) {
                        return new ControllerManager($services, ['factories' => [
                            'bad' => function () {
                                return new BadController();
                            },
                        ]]);
                    },
                ],
                'invokables' => [
                    ViewManager::class => TestAsset\MockViewManager::class,
                ],
            ],
            'router' => [
                'routes' => [
                    'path' => [
                        'type' => Router\Route\Literal::class,
                        'options' => [
                            'route' => '/bad',
                            'defaults' => [
                                'controller' => 'bad',
                                'action'     => 'test',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $container = ApplicationConfigHelper::configureContainer($config);
        $application = $container->get(Application::class);
        $application->bootstrap();
        return $application;
    }
}
