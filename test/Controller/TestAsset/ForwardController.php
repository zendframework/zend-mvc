<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Router\RouteResult;

class ForwardController extends AbstractActionController
{
    public function testAction()
    {
        return ['content' => __METHOD__];
    }

    public function testMatchesAction()
    {
        $e = $this->getEvent();
        return $e->getRequest()->getAttribute(RouteResult::class)->getMatchedParams();
    }

    public function notFoundAction()
    {
        return [
            'status' => 'not-found',
            'params' => $this->params()->fromRoute(),
        ];
    }
}
