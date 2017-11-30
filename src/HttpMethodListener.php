<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;

class HttpMethodListener extends AbstractListenerAggregate
{
    /**
     * @var array
     */
    protected $allowedMethods = [
        RequestMethod::METHOD_HEAD,
        RequestMethod::METHOD_GET,
        RequestMethod::METHOD_POST,
        RequestMethod::METHOD_PUT,
        RequestMethod::METHOD_PATCH,
        RequestMethod::METHOD_DELETE,
        RequestMethod::METHOD_OPTIONS,
        RequestMethod::METHOD_TRACE,
        RequestMethod::METHOD_CONNECT,
        'PROPFIND',
    ];

    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param bool  $enabled
     * @param null|array $allowedMethods
     */
    public function __construct($enabled = true, $allowedMethods = [])
    {
        $this->setEnabled($enabled);

        if (! empty($allowedMethods)) {
            $this->setAllowedMethods($allowedMethods);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_ROUTE,
            [$this, 'onRoute'],
            10000
        );
    }

    /**
     * @param  MvcEvent $e
     * @return null|ResponseInterface
     */
    public function onRoute(MvcEvent $e) : ?ResponseInterface
    {
        $request = $e->getRequest();

        $method = $request->getMethod();

        if (in_array($method, $this->getAllowedMethods())) {
            return null;
        }

        $response = $e->getResponse() ?? new Response();
        $response = $response->withStatus(405);

        return $response;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }

    /**
     * @param array $allowedMethods
     */
    public function setAllowedMethods(array $allowedMethods)
    {
        foreach ($allowedMethods as &$value) {
            $value = strtoupper($value);
        }
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }
}
