<?php
/**
 * @author Stefano Torresi (http://stefanotorresi.it)
 * @license See the file LICENSE.txt for copying permission.
 * ************************************************
 */

namespace ZendTest\Mvc\Controller\TestAsset;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class ServiceLocatorAwareController extends SampleController implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
}
