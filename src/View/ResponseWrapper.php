<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Zend\Mvc\View;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Stream;
use Zend\Stdlib\Response as StdLibResponse;

/**
 * Temporary request/response wrappers until zend-view is updated
 */
class ResponseWrapper extends StdLibResponse
{
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse() : ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response) : void
    {
        $this->response = $response;
    }

    /**
     * Set content
     *
     * @param  mixed $content
     * @return mixed
     */
    public function setContent($content)
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($content);
        $this->response = $this->response->withBody($stream);
    }

    /**
     * Get content
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->response->getBody()->__toString();
    }
}
