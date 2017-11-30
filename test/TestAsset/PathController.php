<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\TestAsset;

use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response;
use Zend\Mvc\Controller\Dispatchable;

class PathController implements Dispatchable
{
    public function dispatch(Request $request)
    {
        $response = new Response();
        $response->getBody()->write(__METHOD__);
        return $response;
    }
}
