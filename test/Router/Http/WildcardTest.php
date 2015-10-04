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
use Zend\Mvc\Router\Http\Wildcard;
use Zend\Stdlib\Request as BaseRequest;
use ZendTest\Mvc\Router\FactoryTester;

class WildcardTest extends TestCase
{
    public static function routeProvider()
    {
        return [
            'simple-match' => [
                new Wildcard(),
                '/foo/bar/baz/bat',
                null,
                ['foo' => 'bar', 'baz' => 'bat']
            ],
            'empty-match' => [
                new Wildcard(),
                '',
                null,
                []
            ],
            'no-match-without-leading-delimiter' => [
                new Wildcard(),
                '/foo/foo/bar/baz/bat',
                5,
                null
            ],
            'no-match-with-trailing-slash' => [
                new Wildcard(),
                '/foo/bar/baz/bat/',
                null,
                null
            ],
            'match-overrides-default' => [
                new Wildcard('/', '/', ['foo' => 'baz']),
                '/foo/bat',
                null,
                ['foo' => 'bat']
            ],
            'offset-skips-beginning' => [
                new Wildcard(),
                '/bat/foo/bar',
                4,
                ['foo' => 'bar']
            ],
            'non-standard-key-value-delimiter' => [
                new Wildcard('-'),
                '/foo-bar/baz-bat',
                null,
                ['foo' => 'bar', 'baz' => 'bat']
            ],
            'non-standard-parameter-delimiter' => [
                new Wildcard('/', '-'),
                '/foo/-foo/bar-baz/bat',
                5,
                ['foo' => 'bar', 'baz' => 'bat']
            ],
            'empty-values-with-non-standard-key-value-delimiter-are-omitted' => [
                new Wildcard('-'),
                '/foo',
                null,
                [],
                true
            ],
            'url-encoded-parameters-are-decoded' => [
                new Wildcard(),
                '/foo/foo%20bar',
                null,
                ['foo' => 'foo bar']
            ],
        ];
    }

    /**
     * @dataProvider routeProvider
     * @param        Wildcard $route
     * @param        string   $path
     * @param        integer  $offset
     * @param        array    $params
     */
    public function testMatching(Wildcard $route, $path, $offset, array $params = null)
    {
        $request = new Request();
        $request->setUri('http://example.com' . $path);
        $match = $route->match($request, $offset);

        if ($params === null) {
            $this->assertNull($match);
        } else {
            $this->assertInstanceOf('Zend\Mvc\Router\Http\RouteMatch', $match);

            if ($offset === null) {
                $this->assertEquals(strlen($path), $match->getLength());
            }

            foreach ($params as $key => $value) {
                $this->assertEquals($value, $match->getParam($key));
            }
        }
    }

    /**
     * @dataProvider routeProvider
     * @param        Wildcard $route
     * @param        string   $path
     * @param        integer  $offset
     * @param        array    $params
     * @param        boolean  $skipAssembling
     */
    public function testAssembling(Wildcard $route, $path, $offset, array $params = null, $skipAssembling = false)
    {
        if ($params === null || $skipAssembling) {
            // Data which will not match are not tested for assembling.
            return;
        }

        $result = $route->assemble($params);

        if ($offset !== null) {
            $this->assertEquals($offset, strpos($path, $result, $offset));
        } else {
            $this->assertEquals($path, $result);
        }
    }

    public function testNoMatchWithoutUriMethod()
    {
        $route   = new Wildcard();
        $request = new BaseRequest();

        $this->assertNull($route->match($request));
    }

    public function testGetAssembledParams()
    {
        $route = new Wildcard();
        $route->assemble(['foo' => 'bar']);

        $this->assertEquals(['foo'], $route->getAssembledParams());
    }

    public function testFactory()
    {
        $tester = new FactoryTester($this);
        $tester->testFactory(
            'Zend\Mvc\Router\Http\Wildcard',
            [],
            []
        );
    }

    public function testRawDecode()
    {
        // verify all characters which don't absolutely require encoding pass through match unchanged
        // this includes every character other than #, %, / and ?
        $raw = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`-=[]\\;\',.~!@$^&*()_+{}|:"<>';
        $request = new Request();
        $request->setUri('http://example.com/foo/' . $raw);
        $route   = new Wildcard();
        $match   = $route->match($request);

        $this->assertSame($raw, $match->getParam('foo'));
    }

    public function testEncodedDecode()
    {
        // every character
        $in  = '%61%62%63%64%65%66%67%68%69%6a%6b%6c%6d%6e%6f%70%71%72%73%74%75%76%77%78%79%7a%41%42%43%44%45%46%47%48%49%4a%4b%4c%4d%4e%4f%50%51%52%53%54%55%56%57%58%59%5a%30%31%32%33%34%35%36%37%38%39%60%2d%3d%5b%5d%5c%3b%27%2c%2e%2f%7e%21%40%23%24%25%5e%26%2a%28%29%5f%2b%7b%7d%7c%3a%22%3c%3e%3f';
        $out = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789`-=[]\\;\',./~!@#$%^&*()_+{}|:"<>?';
        $request = new Request();
        $request->setUri('http://example.com/foo/' . $in);
        $route   = new Wildcard();
        $match   = $route->match($request);

        $this->assertSame($out, $match->getParam('foo'));
    }
}
