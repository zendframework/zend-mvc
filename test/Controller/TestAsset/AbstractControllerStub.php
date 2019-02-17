<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\MvcEvent;

class AbstractControllerStub extends AbstractController
{
    public function onDispatch(MvcEvent $e)
    {
        // noop
    }
}
