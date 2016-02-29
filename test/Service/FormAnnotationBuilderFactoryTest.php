<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\Service\FormAnnotationBuilderFactory;
use Zend\ServiceManager\ServiceManager;

class FormAnnotationBuilderFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Re-enable once zend-form is migrated to zend-servicemanager v3');
    }

    public function testCreateService()
    {
        $mockElementManager = $this->getMock('Zend\Form\FormElementManager');

        $serviceLocator = new ServiceManager();
        $serviceLocator->setService('FormElementManager', $mockElementManager);
        $serviceLocator->setService('config', []);

        $sut = new FormAnnotationBuilderFactory();

        $this->assertInstanceOf('\Zend\Form\Annotation\AnnotationBuilder', $sut->createService($serviceLocator));
    }

    public function testCreateServiceSetsPreserveDefinedOrder()
    {
        $mockElementManager = $this->getMock('Zend\Form\FormElementManager');

        $serviceLocator = new ServiceManager();
        $serviceLocator->setService('FormElementManager', $mockElementManager);
        $config = ['form_annotation_builder' => ['preserve_defined_order' => true]];
        $serviceLocator->setService('config', $config);

        $sut = new FormAnnotationBuilderFactory();

        $service = $sut->createService($serviceLocator);

        $this->assertTrue($service->preserveDefinedOrder(), 'Preserve defined order was not set correctly');
    }
}
