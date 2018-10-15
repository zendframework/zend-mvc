<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\View\Http;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Exception;

class InjectRoutematchParamsListener extends AbstractListenerAggregate
{
    /**
     * Should request params overwrite existing request params?
     *
     * @var bool
     */
    protected $overwrite = true;

    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'injectParams'], 90);
    }

    /**
     * Take parameters from RouteMatch and inject them into the request.
     *
     * @param  MvcEvent $e
     * @return void
     */
    public function injectParams(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();

        if (null === $routeMatch) {
            throw new Exception\RuntimeException('No RouteMatch in event');
        }

        $routeMatchParams = $routeMatch->getParams();
        $request = $e->getRequest();

        if (! $request instanceof HttpRequest) {
            // unsupported request type
            return;
        }

        $params = $request->getQuery();

        if ($this->overwrite) {
            // Overwrite existing parameters, or create new ones if not present.
            foreach ($routeMatchParams as $key => $val) {
                $params->$key = $val;
            }
            return;
        }

        // Only create new parameters.
        foreach ($routeMatchParams as $key => $val) {
            if (! $params->offsetExists($key)) {
                $params->$key = $val;
            }
        }
    }

    /**
     * Should RouteMatch parameters replace existing Request params?
     *
     * @param  bool $overwrite
     */
    public function setOverwrite($overwrite)
    {
        $this->overwrite = $overwrite;
    }

    /**
     * @return bool
     */
    public function getOverwrite()
    {
        return $this->overwrite;
    }
}
