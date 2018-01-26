<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\EventManager\EventsCapableInterface;

interface ApplicationInterface extends EventsCapableInterface, RequestHandlerInterface
{
    /**
     * Get the locator object
     *
     * @return ContainerInterface
     */
    public function getContainer() : ContainerInterface;

    /**
     * Run the application
     *
     * @param ServerRequestInterface|null $request
     * @return void
     */
    public function run(ServerRequestInterface $request = null) : void;
}
