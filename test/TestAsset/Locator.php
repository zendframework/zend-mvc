<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Mvc\TestAsset;

use Zend\Di\Exception\ClassNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Dummy locator used to test handling of locator objects by Application
 */
class Locator implements ServiceLocatorInterface
{
    protected $services = array();

    public function get($name, array $params = array())
    {
        if (!isset($this->services[$name])) {
            throw new ClassNotFoundException();
        }

        $service = call_user_func_array($this->services[$name], $params);
        return $service;
    }

    public function has($name)
    {
        return (isset($this->services[$name]));
    }

    public function add($name, $callback)
    {
        $this->services[$name] = $callback;
    }

    public function remove($name)
    {
        if (isset($this->services[$name])) {
            unset($this->services[$name]);
        }
    }
}
