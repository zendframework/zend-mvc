<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Controller\Plugin;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\Mvc\Exception;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;

/**
 * @todo       allow specifying status code as a default, or as an option to methods
 */
class Redirect extends AbstractPlugin
{
    protected $event;

    /**
     * Generate redirect response based on given route
     *
     * @param  string $route RouteInterface name
     * @param  array $params Parameters to use in url generation, if any
     * @param  array $options RouteInterface-specific options to use in url generation, if any
     * @param  bool $reuseMatchedParams Whether to reuse matched parameters
     * @return ResponseInterface
     * @throws Exception\DomainException if composed controller does not implement InjectApplicationEventInterface, or
     *         router cannot be found in controller event
     */
    public function toRoute(
        $route = null,
        $params = [],
        $options = [],
        $reuseMatchedParams = false
    ) : ResponseInterface {
        $controller = $this->getController();
        if (! $controller || ! method_exists($controller, 'plugin')) {
            throw new Exception\DomainException(
                'Redirect plugin requires a controller that defines the plugin() method'
            );
        }

        $urlPlugin = $controller->plugin('url');

        if (is_scalar($options)) {
            $url = $urlPlugin->fromRoute($route, $params, $options);
        } else {
            $url = $urlPlugin->fromRoute($route, $params, $options, $reuseMatchedParams);
        }

        return $this->toUrl($url);
    }

    /**
     * Generate redirect response based on given URL
     *
     * @param  string $url
     * @return ResponseInterface
     */
    public function toUrl($url) : ResponseInterface
    {
        $response = $this->getResponse();
        $response = $response->withStatus(302)
            ->withoutHeader('Location')
            ->withAddedHeader('Location', $url);
        return $response;
    }

    /**
     * Refresh to current route
     *
     * @return ResponseInterface
     */
    public function refresh() : ResponseInterface
    {
        return $this->toRoute(null, [], [], true);
    }

    /**
     * Get the response
     *
     * @return ResponseInterface
     * @throws Exception\DomainException if unable to find response
     */
    protected function getResponse() : ResponseInterface
    {
        $event    = $this->getEvent();
        $response = $event->getResponse() ?? new Response();
        return $response;
    }

    /**
     * Get the event
     *
     * @return MvcEvent
     * @throws Exception\DomainException if unable to find event
     */
    protected function getEvent() : MvcEvent
    {
        if ($this->event) {
            return $this->event;
        }

        $controller = $this->getController();
        if (! $controller instanceof InjectApplicationEventInterface) {
            throw new Exception\DomainException(
                'Redirect plugin requires a controller that implements InjectApplicationEventInterface'
            );
        }

        $event = $controller->getEvent();
        if (! $event instanceof MvcEvent) {
            $params = $event->getParams();
            $event  = new MvcEvent();
            $event->setParams($params);
        }
        $this->event = $event;

        return $this->event;
    }
}
