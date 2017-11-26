<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Mvc\Controller\Plugin;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\UploadedFile;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteResult;
use ZendTest\Mvc\Controller\TestAsset\SampleController;

class ParamsTest extends TestCase
{
    /**
     * @var ServerRequestInterface
     */
    public $request;

    public function setUp()
    {
        $request = new ServerRequest([], [], null, 'GET', 'php://memory');
        $request = $request->withAttribute('value', 'rm:1234');
        $request = $request->withAttribute('other', '1234:rm');
        $request = $request->withAttribute(RouteResult::class, RouteResult::fromRouteMatch([]));
        $this->request = $request;
        $event         = new MvcEvent;

        $event->setRequest($this->request);
        $event->setResponse(new Response());

        $this->controller = new SampleController();
        $this->controller->setEvent($event);

        $this->plugin = $this->controller->plugin('params');
    }

    public function testFromRouteIsDefault()
    {
        $value = $this->plugin->__invoke('value');
        $this->assertEquals($value, 'rm:1234');
    }

    public function testFromRouteReturnsDefaultIfSet()
    {
        $value = $this->plugin->fromRoute('foo', 'bar');
        $this->assertEquals($value, 'bar');
    }

    public function testFromRouteReturnsExpectedValue()
    {
        $value = $this->plugin->fromRoute('value');
        $this->assertEquals($value, 'rm:1234');
    }

    public function testFromRouteNotReturnsExpectedValueWithDefault()
    {
        $value = $this->plugin->fromRoute('value', 'default');
        $this->assertEquals($value, 'rm:1234');
    }

    public function testFromRouteReturnsAllIfEmpty()
    {
        $value = $this->plugin->fromRoute();
        $this->assertEquals($this->request->getAttributes(), $value);
    }

    public function testFromQueryReturnsDefaultIfSet()
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery('foo', 'bar');
        $this->assertEquals($value, 'bar');
    }

    public function testFromQueryReturnsExpectedValue()
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery('value');
        $this->assertEquals($value, 'query:1234');
    }

    public function testFromQueryReturnsExpectedValueWithDefault()
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery('value', 'default');
        $this->assertEquals($value, 'query:1234');
    }

    public function testFromQueryReturnsAllIfEmpty()
    {
        $this->setQuery();

        $value = $this->plugin->fromQuery();
        $this->assertEquals($value, ['value' => 'query:1234', 'other' => '1234:other']);
    }

    public function testFromPostReturnsDefaultIfSet()
    {
        $this->setPost();

        $value = $this->plugin->fromPost('foo', 'bar');
        $this->assertEquals($value, 'bar');
    }

    public function testFromPostReturnsExpectedValue()
    {
        $this->setPost();

        $value = $this->plugin->fromPost('value');
        $this->assertEquals($value, 'post:1234');
    }

    public function testFromPostReturnsExpectedValueWithDefault()
    {
        $this->setPost();

        $value = $this->plugin->fromPost('value', 'default');
        $this->assertEquals($value, 'post:1234');
    }

    public function testFromPostReturnsAllIfEmpty()
    {
        $this->setPost();

        $value = $this->plugin->fromPost();
        $this->assertEquals($value, ['value' => 'post:1234', 'other' => '2345:other']);
    }

    public function testFromFilesReturnsExpectedValue()
    {
        $file = new UploadedFile(
            '/tmp/' . uniqid(),
            0,
            \UPLOAD_ERR_OK,
            'test.txt',
            'text/plain'
        );
        $request = $this->request->withUploadedFiles(['test' => $file]);
        $this->controller->dispatch($request);

        $value = $this->plugin->fromFiles('test');
        $this->assertEquals($value, $file);
    }

    public function testFromFilesReturnsAllIfEmpty()
    {
        $file = new UploadedFile(
            '/tmp/' . uniqid(),
            0,
            \UPLOAD_ERR_OK,
            'test.txt',
            'text/plain'
        );

        $file2 = new UploadedFile(
            '/tmp/' . uniqid(),
            1,
            \UPLOAD_ERR_OK,
            'file2.txt',
            'text/plain'
        );

        $request = $this->request->withUploadedFiles(['file' => $file, 'file2' => $file2]);

        $this->controller->dispatch($request);

        $value = $this->plugin->fromFiles();
        $this->assertEquals($value, ['file' => $file, 'file2' => $file2]);
    }

    public function testFromHeaderReturnsExpectedValue()
    {
        $request = $this->request->withAddedHeader('X-TEST', 'test');
        $this->controller->dispatch($request);

        $value = $this->plugin->fromHeader('X-TEST');
        $this->assertSame($value, ['test']);
    }

    public function testFromHeaderReturnsAllIfEmpty()
    {
        $request = $this->request->withAddedHeader('X-TEST', 'test')
            ->withAddedHeader('OTHER-TEST', 'value:12345');

        $this->controller->dispatch($request);

        $value = $this->plugin->fromHeader();
        $this->assertSame($value, ['X-TEST' => ['test'], 'OTHER-TEST' => ['value:12345']]);
    }

    public function testInvokeWithNoArgumentsReturnsInstance()
    {
        $this->assertSame($this->plugin, $this->plugin->__invoke());
    }

    protected function setQuery()
    {
        $request = $this->request->withMethod('GET')
            ->withQueryParams([
                'value' => 'query:1234',
                'other' => '1234:other',
            ]);

        $this->controller->dispatch($request);
    }

    protected function setPost()
    {
        $request = $this->request->withMethod('POST')
            ->withParsedBody([
                'value' => 'post:1234',
                'other' => '2345:other',
            ]);

        $this->controller->dispatch($request);
    }
}
