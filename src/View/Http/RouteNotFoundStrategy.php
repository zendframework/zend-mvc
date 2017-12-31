<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\View\Http;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Application;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteResult;
use Zend\View\Model\ViewModel;

class RouteNotFoundStrategy extends AbstractListenerAggregate
{
    /**
     * Whether or not to display exceptions related to the 404 condition
     *
     * @var bool
     */
    protected $displayExceptions = false;

    /**
     * Whether or not to display the reason for a 404
     *
     * @var bool
     */
    protected $displayNotFoundReason = false;

    /**
     * Template to use to report page not found conditions
     *
     * @var string
     */
    protected $notFoundTemplate = 'error';

    /**
     * The reason for a not-found condition
     *
     * @var false|string
     */
    protected $reason = false;

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1) : void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'prepareNotFoundViewModel'], -90);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'detectNotFoundError']);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'prepareNotFoundViewModel']);
    }

    /**
     * Set value indicating whether or not to display exceptions related to a not-found condition
     *
     * @param  bool $displayExceptions
     * @return void
     */
    public function setDisplayExceptions(bool $displayExceptions) : void
    {
        $this->displayExceptions = $displayExceptions;
    }

    /**
     * Should we display exceptions related to a not-found condition?
     *
     * @return bool
     */
    public function displayExceptions() : bool
    {
        return $this->displayExceptions;
    }

    /**
     * Set value indicating whether or not to display the reason for a not-found condition
     *
     * @param  bool $displayNotFoundReason
     * @return void
     */
    public function setDisplayNotFoundReason(bool $displayNotFoundReason) : void
    {
        $this->displayNotFoundReason = $displayNotFoundReason;
    }

    /**
     * Should we display the reason for a not-found condition?
     *
     * @return bool
     */
    public function displayNotFoundReason() : bool
    {
        return $this->displayNotFoundReason;
    }

    /**
     * Get template for not found conditions
     *
     * @param  string $notFoundTemplate
     * @return void
     */
    public function setNotFoundTemplate(string $notFoundTemplate) : void
    {
        $this->notFoundTemplate = $notFoundTemplate;
    }

    /**
     * Get template for not found conditions
     *
     * @return string
     */
    public function getNotFoundTemplate() : string
    {
        return $this->notFoundTemplate;
    }

    /**
     * Detect if an error is a 404 condition
     *
     * If a "controller not found" or "invalid controller" error type is
     * encountered, sets the response status code to 404.
     *
     * @param  MvcEvent $e
     * @return void
     */
    public function detectNotFoundError(MvcEvent $e) : void
    {
        $error = $e->getError();
        if (empty($error)) {
            return;
        }

        switch ($error) {
            case Application::ERROR_CONTROLLER_NOT_FOUND:
            case Application::ERROR_CONTROLLER_INVALID:
            case Application::ERROR_ROUTER_NO_MATCH:
                $this->reason = $error;
                $response = $e->getResponse();
                if (! $response) {
                    // @TODO inject and use response factory
                    $response = new Response();
                }
                $response = $response->withStatus(404);
                $e->setResponse($response);
                break;
            default:
                return;
        }
    }

    /**
     * Create and return a 404 view model
     *
     * @param  MvcEvent $e
     * @return void
     */
    public function prepareNotFoundViewModel(MvcEvent $e) : void
    {
        $vars = $e->getResult();
        if ($vars instanceof ResponseInterface) {
            // Already have a response as the result
            return;
        }

        $response = $e->getResponse();
        if (! $response || $response->getStatusCode() != 404) {
            // Only handle 404 responses
            return;
        }

        if (! $vars instanceof ViewModel) {
            $model = new ViewModel();
            if (is_string($vars)) {
                $model->setVariable('message', $vars);
            } else {
                $model->setVariable('message', 'Page not found.');
            }
        } else {
            $model = $vars;
            if ($model->getVariable('message') === null) {
                $model->setVariable('message', 'Page not found.');
            }
        }

        $model->setTemplate($this->getNotFoundTemplate());

        // If displaying reasons, inject the reason
        $this->injectNotFoundReason($model);

        // If displaying exceptions, inject
        $this->injectException($model, $e);

        // Inject controller if we're displaying either the reason or the exception
        $this->injectController($model, $e);

        $e->setResult($model);
    }

    /**
     * Inject the not-found reason into the model
     *
     * If $displayNotFoundReason is enabled, checks to see if $reason is set,
     * and, if so, injects it into the model. If not, it injects
     * Application::ERROR_CONTROLLER_CANNOT_DISPATCH.
     *
     * @param  ViewModel $model
     * @return void
     */
    protected function injectNotFoundReason(ViewModel $model) : void
    {
        if (! $this->displayNotFoundReason()) {
            return;
        }

        // no route match, controller not found, or controller invalid
        if ($this->reason) {
            $model->setVariable('reason', $this->reason);
            return;
        }

        // otherwise, must be a case of the controller not being able to
        // dispatch itself.
        $model->setVariable('reason', Application::ERROR_CONTROLLER_CANNOT_DISPATCH);
    }

    /**
     * Inject the exception message into the model
     *
     * If $displayExceptions is enabled, and an exception is found in the
     * event, inject it into the model.
     *
     * @param  ViewModel $model
     * @param  MvcEvent $e
     * @return void
     */
    protected function injectException($model, $e) : void
    {
        if (! $this->displayExceptions()) {
            return;
        }

        $model->setVariable('display_exceptions', true);

        $exception = $e->getParam('exception', false);

        if (! $exception instanceof \Throwable) {
            return;
        }

        $model->setVariable('exception', $exception);
    }

    /**
     * Inject the controller and controller class into the model
     *
     * If either $displayExceptions or $displayNotFoundReason are enabled,
     * injects the controllerClass from the MvcEvent. It checks to see if a
     * controller is present in the MvcEvent, and, if not, grabs it from
     * the route match if present; if a controller is found, it injects it into
     * the model.
     *
     * @param  ViewModel $model
     * @param  MvcEvent $e
     * @return void
     */
    protected function injectController($model, MvcEvent $e) : void
    {
        if (! $this->displayExceptions() && ! $this->displayNotFoundReason()) {
            return;
        }

        $controller = $e->getController();
        if (empty($controller)) {
            if (! $e->getRequest()) {
                return;
            }
            /** @var RouteResult $result */
            $result = $e->getRequest()->getAttribute(RouteResult::class);
            if (empty($result)) {
                return;
            }

            $controller = $result->getMatchedParams()['controller'] ?? null;
            if (! $controller) {
                return;
            }
        }

        $controllerClass = $e->getControllerClass();
        $model->setVariable('controller', $controller);
        $model->setVariable('controller_class', $controllerClass);
    }
}
