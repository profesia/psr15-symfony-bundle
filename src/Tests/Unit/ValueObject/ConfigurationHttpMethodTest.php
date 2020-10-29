<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\AbstractHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;

class ConfigurationHttpMethodTest extends MockeryTestCase
{
    public function testCanAssignMiddlewareChainToAnyHttpMethod()
    {
        $httpMethod = ConfigurationHttpMethod::createDefault();

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        $allHttpMethods = AbstractHttpMethod::getPossibleValues();
        $middlewares    = [];
        foreach ($allHttpMethods as $method) {
            $middlewares[$method] = $middleware;
        }

        $returnValue = $httpMethod->assignMiddlewareChainToHttpMethods($middlewares);

        $this->assertCount(count($allHttpMethods), $returnValue);
        foreach ($allHttpMethods as $method) {
            $this->assertEquals($returnValue[$method], $middleware);
        }
    }

    public function testCanAssignMiddlewareChainToSpecificHttpMethod()
    {
        $httpMethod = ConfigurationHttpMethod::createFromString('GET');

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware  = Mockery::mock(AbstractMiddlewareChainItem::class);

        $middlewares    = [
            'GET' => $middleware
        ];
        $returnValue = $httpMethod->assignMiddlewareChainToHttpMethods($middlewares);

        $this->assertCount(1, $returnValue);
        $this->assertEquals($returnValue['GET'], $middleware);
    }

    public function testCanHandleMultipleHttpMethods()
    {
        $httpMethods         = [
            'GET',
            'POST',
            'PUT'
        ];
        $implodedHttpMethods = implode('|', $httpMethods);
        $httpMethod          = ConfigurationHttpMethod::createFromString($implodedHttpMethods);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware     = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middlewares = [];
        foreach ($httpMethods as $method) {
            $middlewares[$method] = $middleware;
        }

        $returnValue    = $httpMethod->assignMiddlewareChainToHttpMethods($middlewares);
        $string         = $httpMethod->toString();
        $explodedString = explode('|', $string);

        $this->assertEquals($implodedHttpMethods, $string);
        $this->assertCount(3, $returnValue);

        $index = 0;
        foreach ($httpMethods as $httpMethod) {
            $this->assertEquals($middleware, $returnValue[$httpMethod]);
            $this->assertEquals($httpMethod, $explodedString[$index]);
            $index++;
        }
    }

    public function testCanHandleUnsupportedHttpMethodInInputString()
    {
        $unsupportedString = 'assdaads';
        $httpMethods       = [
            'GET',
            $unsupportedString,
            'PUT'
        ];

        $implodedHttpMethods = implode('|', $httpMethods);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value: [{$unsupportedString}] is not supported");
        ConfigurationHttpMethod::createFromString($implodedHttpMethods);
    }
}