<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Controller\Plugin;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\InjectApplicationEventInterface;

class Params extends AbstractPlugin
{
    /**
     * Grabs a param from route match by default.
     *
     * @param string $param
     * @param mixed $default
     * @return mixed
     */
    public function __invoke($param = null, $default = null)
    {
        if ($param === null) {
            return $this;
        }
        return $this->fromRoute($param, $default);
    }

    /**
     * Return all files or a single file.
     *
     * @param  string $name File name to retrieve, or null to get all.
     * @param  mixed $default Default value to use when the file is missing.
     * @return array|UploadedFileInterface[]|UploadedFileInterface|null
     */
    public function fromFiles($name = null, $default = null)
    {
        $files = $this->getController()->getRequest()->getUploadedFiles();

        if ($name === null) {
            return $files;
        }

        return $files[$name] ?? $default;
    }

    /**
     * Return all header parameters or a single header parameter.
     *
     * @param  string $header Header name to retrieve, or null to get all.
     * @param  mixed $default Default value to use when the requested header is missing.
     * @return string[][]|string[]
     */
    public function fromHeader($header = null, array $default = [])
    {
        /**
         * @var ServerRequestInterface $request
         */
        $request = $this->getController()->getRequest();
        if ($header === null) {
            return $request->getHeaders();
        }

        if (! $request->hasHeader($header)) {
            return $default;
        }

        return $request->getHeader($header);
    }

    /**
     * Return all post parameters or a single post parameter.
     *
     * @param string $param Parameter name to retrieve, or null to get all.
     * @param mixed $default Default value to use when the parameter is missing.
     * @return mixed
     */
    public function fromPost($param = null, $default = null)
    {
        $parsedBody = $this->getController()->getRequest()->getParsedBody();
        if ($param === null) {
            return $parsedBody;
        }

        return $parsedBody[$param] ?? $default;
    }

    /**
     * Return all query parameters or a single query parameter.
     *
     * @param string $param Parameter name to retrieve, or null to get all.
     * @param mixed $default Default value to use when the parameter is missing.
     * @return mixed
     */
    public function fromQuery($param = null, $default = null)
    {
        $query = $this->getController()->getRequest()->getQueryParams();
        if ($param === null) {
            return $query;
        }

        return $query[$param] ?? $default;
    }

    /**
     * Return all route parameters or a single route parameter.
     *
     * @param string $param Parameter name to retrieve, or null to get all.
     * @param mixed $default Default value to use when the parameter is missing.
     * @return mixed
     * @throws RuntimeException
     */
    public function fromRoute($param = null, $default = null)
    {
        $controller = $this->getController();

        if ($param === null) {
            return $controller->getRequest()->getAttributes();
        }

        return $controller->getRequest()->getAttribute($param, $default);
    }
}
