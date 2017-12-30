<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Container;

use Psr\Container\ContainerInterface;
use Zend\View\Strategy\PhpRendererStrategy;
use Zend\View\View;

class ViewFactory
{
    /**
     * @param  ContainerInterface $container
     * @return View
     */
    public function __invoke(ContainerInterface $container) : View
    {
        $view   = new View();
        $events = $container->get('EventManager');

        $view->setEventManager($events);
        $container->get(PhpRendererStrategy::class)->attach($events);

        return $view;
    }
}
