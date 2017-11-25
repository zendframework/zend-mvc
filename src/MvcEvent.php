<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\EventManager\Event;
use Zend\Router\RouteStackInterface;
use Zend\View\Model\ModelInterface as Model;
use Zend\View\Model\ViewModel;

class MvcEvent extends Event
{
    /**#@+
     * Mvc events triggered by eventmanager
     */
    const EVENT_BOOTSTRAP      = 'bootstrap';
    const EVENT_DISPATCH       = 'dispatch';
    const EVENT_DISPATCH_ERROR = 'dispatch.error';
    const EVENT_FINISH         = 'finish';
    const EVENT_RENDER         = 'render';
    const EVENT_RENDER_ERROR   = 'render.error';
    const EVENT_ROUTE          = 'route';
    /**#@-*/

    protected $application;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var RouteStackInterface
     */
    protected $router;

    /**
     * @var Model
     */
    protected $viewModel;

    /**
     * Set application instance
     *
     * @param  ApplicationInterface $application
     * @return void
     */
    public function setApplication(ApplicationInterface $application) : void
    {
        $this->setParam('application', $application);
        $this->application = $application;
    }

    /**
     * Get application instance
     *
     * @return ApplicationInterface
     */
    public function getApplication() : ApplicationInterface
    {
        return $this->application;
    }

    /**
     * Get router
     *
     * @return RouteStackInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Set router
     *
     * @param RouteStackInterface $router
     * @return void
     */
    public function setRouter(RouteStackInterface $router) : void
    {
        $this->setParam('router', $router);
        $this->router = $router;
    }

    /**
     * Get request. Not available during bootstrap
     *
     * @return Request
     */
    public function getRequest() : ?Request
    {
        return $this->request;
    }

    /**
     * Set request
     *
     * @param Request $request
     * @return void
     */
    public function setRequest(Request $request) : void
    {
        $this->setParam('request', $request);
        $this->request = $request;
    }

    /**
     * Get response
     *
     * @return ResponseInterface
     */
    public function getResponse() : ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Set response
     *
     * @param ResponseInterface $response
     * @return void
     */
    public function setResponse(ResponseInterface $response) : void
    {
        $this->setParam('response', $response);
        $this->response = $response;
    }

    /**
     * Set the view model
     *
     * @param  Model $viewModel
     * @return void
     */
    public function setViewModel(Model $viewModel) : void
    {
        $this->viewModel = $viewModel;
    }

    /**
     * Get the view model
     *
     * @return Model
     */
    public function getViewModel() : Model
    {
        if (null === $this->viewModel) {
            $this->setViewModel(new ViewModel());
        }
        return $this->viewModel;
    }

    /**
     * Get result
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set result
     *
     * @param mixed $result
     * @return MvcEvent
     */
    public function setResult($result) : void
    {
        $this->setParam('__RESULT__', $result);
        $this->result = $result;
    }

    /**
     * Does the event represent an error response?
     *
     * @return bool
     */
    public function isError() : bool
    {
        return (bool) $this->getParam('error', false);
    }

    /**
     * Set the error message (indicating error in handling request)
     *
     * @param  string $message
     * @return void
     */
    public function setError($message) : void
    {
        $this->setParam('error', $message);
    }

    /**
     * Retrieve the error message, if any
     *
     * @return string
     */
    public function getError()
    {
        return $this->getParam('error', '');
    }

    /**
     * Get the currently registered controller name
     *
     * @return string
     */
    public function getController()
    {
        return $this->getParam('controller');
    }

    /**
     * Set controller name
     *
     * @param  string $name
     * @return MvcEvent
     */
    public function setController($name)
    {
        $this->setParam('controller', $name);
        return $this;
    }

    /**
     * Get controller class
     *
     * @return string
     */
    public function getControllerClass()
    {
        return $this->getParam('controller-class');
    }

    /**
     * Set controller class
     *
     * @param string $class
     * @return MvcEvent
     */
    public function setControllerClass($class)
    {
        $this->setParam('controller-class', $class);
        return $this;
    }
}
