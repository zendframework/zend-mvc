<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */
declare(strict_types=1);

namespace Zend\Mvc\View;

use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stdlib\Request as StdLibRequest;

/**
 * Temporary request/response wrappers until zend-view is updated
 */
class RequestWrapper extends StdLibRequest
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest() : Request
    {
        return $this->request;
    }

    public function setRequest(Request $request) : void
    {
        $this->request = $request;
    }

    /**
     * Set content
     *
     * @param  mixed $content
     * @return mixed
     */
    public function setContent($content)
    {
    }

    /**
     * Get content
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->request->getBody()->__toString();
    }
}
