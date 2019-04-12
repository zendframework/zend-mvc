<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Service;

use Psr\Container\ContainerInterface;
use Zend\Mvc\View\Http\ViewManager as HttpViewManager;

class HttpViewManagerFactory
{
    /**
     * Create and return a view manager for the HTTP environment
     */
    public function __invoke(ContainerInterface $container) : HttpViewManager
    {
        return new HttpViewManager();
    }
}
