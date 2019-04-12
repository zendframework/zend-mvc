<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Service;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\Service\RequestFactory;

class RequestFactoryTest extends TestCase
{
    public function testFactoryCreatesHttpRequest()
    {
        $factory = new RequestFactory();
        $request = $factory($this->prophesize(ContainerInterface::class)->reveal(), 'Request');
        $this->assertInstanceOf(HttpRequest::class, $request);
    }
}
