<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Router\RouteResult;

class RouteListener extends AbstractListenerAggregate
{
    /**
     * Attach to an event manager
     *
     * @param  EventManagerInterface $events
     * @param  int $priority
     * @return void
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute']);
    }

    /**
     * Listen to the "route" event and attempt to route the request
     *
     * If no matches are returned, triggers "dispatch.error" in order to
     * create a 404 response.
     *
     * Seeds the event with the route match on completion.
     *
     * @param  MvcEvent $event
     * @return null|RouteResult|mixed
     */
    public function onRoute(MvcEvent $event)
    {
        $request    = $event->getRequest();
        $router     = $event->getRouter();
        $routeResult = $router->match($request);

        if ($routeResult->isSuccess()) {
            foreach ($routeResult->getMatchedParams() as $name => $param) {
                $request = $request->withAttribute($name, $param);
            }
            $request = $request->withAttribute(RouteResult::class, $routeResult);
            $event->setRequest($request);
            return $routeResult;
        }

        $event->setName(MvcEvent::EVENT_DISPATCH_ERROR);
        $event->setError(Application::ERROR_ROUTER_NO_MATCH);
        $event->setParam(RouteResult::class, $routeResult);

        $target  = $event->getTarget();
        $results = $target->getEventManager()->triggerEvent($event);
        if (! empty($results)) {
            return $results->last();
        }

        return $event->getParams();
    }
}
