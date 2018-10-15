<?php

namespace ZendTest\Mvc\Exception;

use ArrayObject;
use Zend\Mvc\Exception\InvalidArgumentException;

class InvalidArgumentExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testUnexpectedTypeWithObjectType()
    {
        $object = new ArrayObject();
        $exception = InvalidArgumentException::unexpectedType('foo', $object);

        $this->assertSame('Expected foo. ArrayObject given', $exception->getMessage());
    }

    public function testUnexpectedTypeWithScalarType()
    {
        $exception = InvalidArgumentException::unexpectedType('foo', 5);

        $this->assertSame('Expected foo. integer given', $exception->getMessage());
    }
}