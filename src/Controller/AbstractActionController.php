<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Controller;

use Zend\Diactoros\Response;
use Zend\Mvc\Exception;
use Zend\Mvc\MvcEvent;
use Zend\Router\RouteResult;
use Zend\View\Model\ViewModel;

/**
 * Basic action controller
 */
abstract class AbstractActionController extends AbstractController
{
    /**
     * {@inheritDoc}
     */
    protected $eventIdentifier = __CLASS__;

    /**
     * Default action if none provided
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        return new ViewModel([
            'content' => 'Placeholder page'
        ]);
    }

    /**
     * Action called if matched action does not exist
     *
     * @return ViewModel
     */
    public function notFoundAction()
    {
        $event      = $this->getEvent();
        $request = $event->getRequest();
        /** @var RouteResult $routeResult */
        //
        $routeResult = $request->getAttribute(RouteResult::class);
        if (! $routeResult) {
            // this should never be reached normally
            throw new Exception\DomainException('Missing route result');
        }
        $params = $routeResult->getMatchedParams();
        $params['action'] = 'not-found';
        $routeResult = $routeResult->withMatchedParams($params);
        $request = $request->withAttribute(RouteResult::class, $routeResult);
        $event->setRequest($request);

        $response = $this->getResponse() ?? new Response();
        $event->setResponse($response->withStatus(404));
        $helper = $this->plugin('createHttpNotFoundModel');
        return $helper($event->getResponse());
    }

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     * @throws Exception\DomainException
     */
    public function onDispatch(MvcEvent $e)
    {
        /** @var RouteResult $routeResult */
        $routeResult = $e->getRequest()->getAttribute(RouteResult::class);
        if (! $routeResult) {
            /**
             * @todo Determine requirements for when route match is missing.
             *       Potentially allow pulling directly from request metadata?
             */
            throw new Exception\DomainException('Missing route result; unsure how to retrieve action');
        }

        $action = $routeResult->getMatchedParams()['action'] ?? 'not-found';
        $method = static::getMethodFromAction($action);

        if (! method_exists($this, $method)) {
            $method = 'notFoundAction';
        }

        $actionResponse = $this->$method();

        $e->setResult($actionResponse);

        return $actionResponse;
    }
}
