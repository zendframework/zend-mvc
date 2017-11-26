<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
declare(strict_types=1);

namespace Zend\Mvc\Controller;

use Psr\Http\Message\ServerRequestInterface as Request;

interface Dispatchable
{
    public function dispatch(Request $request);
}
