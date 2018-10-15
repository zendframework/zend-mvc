<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\AbstractController;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;

class Params extends AbstractPlugin
{
    private function getPluginController()
    {
        $controller = $this->getController();

        if (! $controller instanceof AbstractController) {
            throw new RuntimeException('Controller is not an instance of ' . AbstractController::class);
        }

        return $controller;
    }

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
     * @return array|\ArrayAccess|null
     */
    public function fromFiles($name = null, $default = null)
    {
        if ($name === null) {
            return $this->getPluginController()->getRequest()->getFiles($name, $default)->toArray();
        }

        return $this->getPluginController()->getRequest()->getFiles($name, $default);
    }

    /**
     * Return all header parameters or a single header parameter.
     *
     * @param  string $header Header name to retrieve, or null to get all.
     * @param  mixed $default Default value to use when the requested header is missing.
     * @return null|\Zend\Http\Header\HeaderInterface
     */
    public function fromHeader($header = null, $default = null)
    {
        if ($header === null) {
            return $this->getPluginController()->getRequest()->getHeaders($header, $default)->toArray();
        }

        return $this->getPluginController()->getRequest()->getHeaders($header, $default);
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
        if ($param === null) {
            return $this->getPluginController()->getRequest()->getPost($param, $default)->toArray();
        }

        return $this->getPluginController()->getRequest()->getPost($param, $default);
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
        if ($param === null) {
            return $this->getPluginController()->getRequest()->getQuery($param, $default)->toArray();
        }

        return $this->getPluginController()->getRequest()->getQuery($param, $default);
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
        $controller = $this->getPluginController();

        if (! $controller instanceof InjectApplicationEventInterface) {
            throw new RuntimeException(
                'Controllers must implement Zend\Mvc\InjectApplicationEventInterface to use this plugin.'
            );
        }

        $event = $controller->getEvent();

        if (! $event instanceof MvcEvent) {
            throw new RuntimeException('Controller event is not an instance of ' . MvcEvent::class);
        }

        $routeMatch = $event->getRouteMatch();

        if (null === $routeMatch) {
            throw new RuntimeException('Controller event has no RouteMatch');
        }

        if ($param === null) {
            return $routeMatch->getParams();
        }

        return $routeMatch->getParam($param, $default);
    }
}
