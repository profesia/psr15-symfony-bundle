<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Symfony\Psr15Bundle\ValueObject\AbstractHttpMethod;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class AbstractHttpMethodTest extends TestCase
{
    public function testCanCompareEquality()
    {
        $httpMethod1 = SimpleHttpMethod::createFromString('GET');
        $httpMethod2 = SimpleHttpMethod::createFromString('GET');
        $httpMethod3 = SimpleHttpMethod::createFromString('POST');

        $this->assertTrue($httpMethod1->equals($httpMethod2));
        $this->assertFalse($httpMethod1->equals($httpMethod3));
    }

    public function testCanCreate()
    {
        $allowedValues = SimpleHttpMethod::getPossibleValues();
        SimpleHttpMethod::createFromString(current($allowedValues));

        $value = '123-testing';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("String: [{$value}] is not a valid value for Configuration HTTP method");
        SimpleHttpMethod::createFromString($value);
    }
}

class SimpleHttpMethod extends AbstractHttpMethod
{

}