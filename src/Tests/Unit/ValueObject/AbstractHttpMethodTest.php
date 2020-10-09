<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AbstractHttpMethodTest extends TestCase
{
    public function testCanCompareEquality()
    {
        $httpMethod1 = HttpMethod::createFromString('GET');
        $httpMethod2 = HttpMethod::createFromString('GET');
        $httpMethod3 = HttpMethod::createFromString('POST');

        $this->assertTrue($httpMethod1->equals($httpMethod2));
        $this->assertFalse($httpMethod1->equals($httpMethod3));
    }

    public function testCanCreate()
    {
        $allowedValues = HttpMethod::getPossibleValues();
        HttpMethod::createFromString(current($allowedValues));

        $value = '123-testing';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("String: [{$value}] is not a valid value for HTTP method");
        HttpMethod::createFromString($value);
    }
}