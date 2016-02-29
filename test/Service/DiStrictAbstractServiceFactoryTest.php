<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Zend\Di\Config;
use Zend\Di\Di;
use Zend\Mvc\Service\DiStrictAbstractServiceFactory;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceManager;

class DiStrictAbstractServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetAllowedServiceNames()
    {
        $instance = new DiStrictAbstractServiceFactory($this->getMock('Zend\Di\Di'));
        $instance->setAllowedServiceNames(['first-service', 'second-service']);
        $allowedServices = $instance->getAllowedServiceNames();
        $this->assertCount(2, $allowedServices);
        $this->assertContains('first-service', $allowedServices);
        $this->assertContains('second-service', $allowedServices);
    }

    public function testWillOnlyCreateServiceInWhitelist()
    {
        $instance = new DiStrictAbstractServiceFactory($this->getMock('Zend\Di\Di'));
        $instance->setAllowedServiceNames(['a-whitelisted-service-name']);
        $im = $instance->instanceManager();
        $im->addSharedInstance(new \stdClass(), 'a-whitelisted-service-name');

        $locator = $this->prophesize(ServiceLocatorInterface::class);
        $locator->willImplement(ContainerInterface::class);

        $this->assertTrue($instance->canCreateServiceWithName(
            $locator->reveal(),
            'a-whitelisted-service-name',
            'a-whitelisted-service-name'
        ));
        $this->assertInstanceOf(
            'stdClass',
            $instance->createServiceWithName(
                $locator->reveal(),
                'a-whitelisted-service-name',
                'a-whitelisted-service-name'
            )
        );

        $this->assertFalse($instance->canCreateServiceWithName(
            $locator->reveal(),
            'not-whitelisted',
            'not-whitelisted'
        ));

        $this->setExpectedException('Zend\ServiceManager\Exception\InvalidServiceException');
        $instance->createServiceWithName($locator->reveal(), 'not-whitelisted', 'not-whitelisted');
    }

    public function testWillFetchDependenciesFromServiceManagerBeforeDi()
    {
        $controllerName = __NAMESPACE__ . '\TestAsset\ControllerWithDependencies';
        $config = new Config([
            'instance' => [
                $controllerName => ['parameters' => ['injected' => 'stdClass']],
            ],
        ]);
        $locator = new ServiceManager();
        $testService = new \stdClass();
        $locator->setService('stdClass', $testService);

        $di = new Di;
        $config->configure($di);
        $instance = new DiStrictAbstractServiceFactory($di, DiStrictAbstractServiceFactory::USE_SL_BEFORE_DI);
        $instance->setAllowedServiceNames([$controllerName]);
        $service = $instance->createServiceWithName($locator, $controllerName, $controllerName);
        $this->assertSame($testService, $service->injectedValue);
    }
}
