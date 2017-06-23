<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Controller\Plugin;

use PHPUnit\Framework\TestCase;
use Zend\Mvc\Controller\Plugin\Layout as LayoutPlugin;
use Zend\Mvc\Exception\DomainException;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

class LayoutTest extends TestCase
{
    public function setUp()
    {
        $this->event      = $event = new MvcEvent();
        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('layout');
    }

    public function testPluginWithoutControllerRaisesDomainException()
    {
        $plugin = new LayoutPlugin();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('requires a controller');
        $plugin->setTemplate('home');
    }

    public function testSetTemplateAltersTemplateInEventViewModel()
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $this->plugin->setTemplate('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testInvokeProxiesToSetTemplate()
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $plugin = $this->plugin;
        $plugin('alternate/layout');
        $this->assertEquals('alternate/layout', $model->getTemplate());
    }

    public function testCallingInvokeWithNoArgumentsReturnsViewModel()
    {
        $model = new ViewModel();
        $model->setTemplate('layout');
        $this->event->setViewModel($model);

        $plugin = $this->plugin;
        $result = $plugin();
        $this->assertSame($model, $result);
    }
}
