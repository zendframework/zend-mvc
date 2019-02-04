<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Psr\Container\ContainerInterface;
use Zend\EventManager\EventsCapableInterface;
use Zend\Stdlib\RequestInterface;
use Zend\Stdlib\ResponseInterface;

interface ApplicationInterface extends EventsCapableInterface
{
    /**
     * Main Container object
     */
    public function getContainer() : ContainerInterface;

    /**
     * Get the request object
     *
     * @return RequestInterface
     */
    public function getRequest();

    /**
     * Get the response object
     *
     * @return ResponseInterface
     */
    public function getResponse();

    /**
     * Run the application
     *
     * @return self
     */
    public function run();
}
