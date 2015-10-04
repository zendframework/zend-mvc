<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Service;

use ArrayObject;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Form\FormElementManager;
use Zend\Mvc\Service\DiAbstractServiceFactoryFactory;
use Zend\Mvc\Service\DiFactory;
use Zend\Mvc\Service\DiServiceInitializerFactory;
use Zend\Mvc\Service\FormElementManagerFactory;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;

class FormElementManagerFactoryTest extends TestCase
{
    /**
     * @var ServiceManager
     */
    protected $services;

    /**
     * @var \Zend\Mvc\Controller\ControllerManager
     */
    protected $loader;

    public function setUp()
    {
        $formElementManagerFactory = new FormElementManagerFactory();
        $config = new ArrayObject(['di' => []]);
        $this->services = new ServiceManager();
        $this->services->setService('Zend\ServiceManager\ServiceLocatorInterface', $this->services);
        $this->services->setFactory('FormElementManager', $formElementManagerFactory);
        $this->services->setService('Config', $config);
        $this->services->setFactory('Di', new DiFactory());
        $this->services->setFactory('DiAbstractServiceFactory', new DiAbstractServiceFactoryFactory());
        $this->services->setFactory('DiServiceInitializer', new DiServiceInitializerFactory());
    }

    public function testWillGetFormElementManager()
    {
        $formElementManager = $this->services->get('FormElementManager');
        $this->assertInstanceof('Zend\Form\FormElementManager', $formElementManager);
    }

    public function testWillInstantiateFormFromInvokable()
    {
        $formElementManager = $this->services->get('FormElementManager');
        $form = $formElementManager->get('form');
        $this->assertInstanceof('Zend\Form\Form', $form);
    }

    public function testWillInstantiateFormFromDiAbstractFactory()
    {
        //without DiAbstractFactory
        $standaloneFormElementManager = new FormElementManager();
        $this->assertFalse($standaloneFormElementManager->has('ZendTest\Mvc\Service\TestAsset\CustomForm'));
        //with DiAbstractFactory
        $formElementManager = $this->services->get('FormElementManager');
        $this->assertTrue($formElementManager->has('ZendTest\Mvc\Service\TestAsset\CustomForm'));
        $form = $formElementManager->get('ZendTest\Mvc\Service\TestAsset\CustomForm');
        $this->assertInstanceof('ZendTest\Mvc\Service\TestAsset\CustomForm', $form);
    }
}
