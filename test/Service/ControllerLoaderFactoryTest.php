<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Controller\ControllerManager;
use Zend\Mvc\Service\ControllerLoaderFactory;
use Zend\ServiceManager\ServiceManager;

class ControllerLoaderFactoryTest extends TestCase
{
    public function testWillInitializeControllerManager()
    {
        $this->markTestSkipped();

        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', []);
        $serviceManager->setFactory(ControllerManager::class, new ControllerLoaderFactory());

        $controllerManager = $serviceManager->get(ControllerManager::class);

        $this->assertInstanceOf(ControllerManager::class, $controllerManager);
    }
}
