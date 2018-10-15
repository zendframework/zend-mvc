<?php

namespace ZendTest\Mvc\Exception;

use ArrayObject;
use Zend\Mvc\Exception\UnexpectedValueException;

class UnexpectedValueExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testUnexpectedTypeWithObjectType()
    {
        $object = new ArrayObject();
        $exception = UnexpectedValueException::unexpectedType('foo', $object);

        $this->assertSame('Expected foo. ArrayObject given', $exception->getMessage());
    }

    public function testUnexpectedTypeWithScalarType()
    {
        $exception = UnexpectedValueException::unexpectedType('foo', 5);

        $this->assertSame('Expected foo. integer given', $exception->getMessage());
    }
}
