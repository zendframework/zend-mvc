<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\ResponseSender;

use Zend\Http\PhpEnvironment\Response;

class PhpEnvironmentResponseSender extends HttpResponseSender
{
    /**
     * Send php environment response
     *
     * @param  SendResponseEvent $event
     * @return PhpEnvironmentResponseSender
     */
    public function __invoke(SendResponseEvent $event)
    {
        $response = $event->getResponse();
        if (! $response instanceof Response) {
            return $this;
        }

        $this->sendHeaders($event);
        $this->sendContent($event);
        $event->stopPropagation(true);
        return $this;
    }
}
