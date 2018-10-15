<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\ResponseSender;

use Zend\Http\Header\HeaderInterface;
use Zend\Http\Header\MultipleHeaderInterface;
use Zend\Http\Response;
use Zend\Mvc\Exception\RuntimeException;
use Zend\Mvc\Exception\UnexpectedValueException;

abstract class AbstractResponseSender implements ResponseSenderInterface
{
    /**
     * Send HTTP headers
     *
     * @param  SendResponseEvent $event
     * @return self
     */
    public function sendHeaders(SendResponseEvent $event)
    {
        if (headers_sent() || $event->headersSent()) {
            return $this;
        }

        $response = $event->getResponse();

        if (! $response instanceof Response) {
            throw new UnexpectedValueException(sprintf(
                'Event response must be an instance of %s. %s given',
                Response::class,
                \is_object($response) ? \get_class($response) : \gettype($response)
            ));
        }

        foreach ($response->getHeaders() as $header) {
            if ($header instanceof MultipleHeaderInterface) {
                header($header->toString(), false);
                continue;
            }

            header($header->toString());
        }

        $status = $response->renderStatusLine();
        header($status);

        $event->setHeadersSent();
        return $this;
    }
}
