<?php
/**
 * @link      http://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Mvc\Exception;

class UnexpectedValueException extends \UnexpectedValueException implements ExceptionInterface
{
    /**
     * @param string $expected
     * @param mixed $actual
     *
     * @return UnexpectedValueException
     */
    public static function unexpectedType($expected, $actual)
    {
        return new static(sprintf(
            'Expected %s. %s given',
            $expected,
            \is_object($actual) ? \get_class($actual) : \gettype($actual)
        ));
    }
}
