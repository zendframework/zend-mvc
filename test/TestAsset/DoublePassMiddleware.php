<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Mvc\TestAsset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DoublePassMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next = null)
    {
        $response->getBody()->write($this->getExpectedOutput());

        return $response;
    }

    /**
     * @return string
     */
    public function getExpectedOutput()
    {
        return '';
    }
}
