<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class HttpMethodTest extends MockeryTestCase
{
    public function testCanCreate()
    {
        foreach (HttpMethod::getPossibleValues() as $value) {
            HttpMethod::createFromString($value);
        }

        $invalidValue = 'testing';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("String: [{$invalidValue}] is not a valid value for HttpMethod");
        HttpMethod::createFromString($invalidValue);
    }

    public function testCanExtractMiddleware()
    {
        $httpMethod  = HttpMethod::createFromString('GET');
        $middlewares = [];

        $returnValue = $httpMethod->extractMiddleware($middlewares);
        $this->assertNull($returnValue);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middlewares = [
            'POST' => $middleware1
        ];

        $returnValue = $httpMethod->extractMiddleware($middlewares);
        $this->assertNull($returnValue);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middlewares = [
            'GET' => $middleware2
        ];

        $returnValue = $httpMethod->extractMiddleware($middlewares);
        $this->assertEquals($middleware2, $returnValue);
    }

    public function testCanCompare()
    {
        $httpMethods = HttpMethod::getPossibleValues();
        $indexes = array_keys($httpMethods);

        foreach ($indexes as $i) {
            foreach ($indexes as $j) {
                $isSame = ($i === $j);

                $v1 = HttpMethod::createFromString($httpMethods[$i]);
                $v2 = HttpMethod::createFromString($httpMethods[$j]);

                $this->assertEquals($isSame, $v1->equals($v2));
            }
        }
    }
}