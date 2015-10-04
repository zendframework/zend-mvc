<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\Router\Http;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Http\Request as Request;
use Zend\Mvc\Router\Http\Query;
use Zend\Stdlib\Request as BaseRequest;
use Zend\Uri\Http;
use ZendTest\Mvc\Router\FactoryTester;

class QueryTest extends TestCase
{
    public function setUp()
    {
        $this->markTestSkipped('Query route part has been deprecated in ZF as of 2.1.4');
    }

    public function routeProvider()
    {
        // Have to setup error handler here as well, as PHPUnit calls on
        // provider methods outside the scope of setUp().
        return [
            'simple-match' => [
                new Query(),
                'foo=bar&baz=bat',
                null,
                ['foo' => 'bar', 'baz' => 'bat']
            ],
            'empty-match' => [
                new Query(),
                '',
                null,
                []
            ],
            'url-encoded-parameters-are-decoded' => [
                new Query(),
                'foo=foo%20bar',
                null,
                ['foo' => 'foo bar']
            ],
            'nested-params' => [
                new Query(),
                'foo%5Bbar%5D=baz&foo%5Bbat%5D=foo%20bar',
                null,
                ['foo' => ['bar' => 'baz', 'bat' => 'foo bar']]
            ],
        ];
    }

    /**
     * @param        Query $route
     * @param        string   $path
     * @param        integer  $offset
     * @param        array    $params
     */
    public function testMatching(Query $route, $path, $offset, array $params = null)
    {
        $request = new Request();
        $request->setUri('http://example.com?' . $path);
        $match = $route->match($request, $offset);
        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $match);
    }

    /**
     * @param        Query $route
     * @param        string   $path
     * @param        integer  $offset
     * @param        array    $params
     * @param        boolean  $skipAssembling
     */
    public function testAssembling(Query $route, $path, $offset, array $params = null, $skipAssembling = false)
    {
        if ($params === null || $skipAssembling) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $uri = new Http();
        $result = $route->assemble($params, ['uri' => $uri]);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $uri->getQuery(), $offset));
        } else {
            $this->assertEquals($path, $uri->getQuery());
        }
    }

    public function testNoMatchWithoutUriMethod()
    {
        $route   = new Query();
        $request = new BaseRequest();
        $match   = $route->match($request);
        $this->assertInstanceOf('Zend\Mvc\Router\RouteMatch', $match);
        $this->assertEquals([], $match->getParams());
    }

    public function testGetAssembledParams()
    {
        $route = new Query();
        $uri = new Http();
        $route->assemble(['foo' => 'bar'], ['uri' => $uri]);


        $this->assertEquals(['foo'], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Zend\Mvc\Router\Http\Query',
            [],
            []
        );
    }
}
