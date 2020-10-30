<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class ConfigurationHttpMethodTest extends MockeryTestCase
{
    public function valuesDataProvider()
    {
        return [
            [
                ['GET'],
                ['GET', 'POST'],
                ['GET', 'POST', 'DELETE', 'PUT']
            ]
        ];
    }

    public function testCanAssignMiddlewareChainToAnyHttpMethod()
    {
        $httpMethod = ConfigurationHttpMethod::createDefault();

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        $allHttpMethods = HttpMethod::getPossibleValues();
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
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        $middlewares = [
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
        $middleware  = Mockery::mock(AbstractMiddlewareChainItem::class);
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

    public function testWillDetectUnregisteredHttpMethodOnExtractionOfAMiddleware()
    {
        $httpMethods = [
            'GET',
            'POST',
            'PUT',
            'DELETE'
        ];

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        $middlewares = [
            'GET'  => $middleware,
            'POST' => $middleware,
            'PUT'  => $middleware
        ];

        $configurationHttpMethod = ConfigurationHttpMethod::createFromString(
            implode('|', $httpMethods)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value: [DELETE] is not present in the middleware chain array');
        $configurationHttpMethod->assignMiddlewareChainToHttpMethods($middlewares);
    }

    public function testCanCastToArray()
    {
        $httpMethods = [
            'GET',
            'POST',
            'PUT',
            'DELETE'
        ];

        $configurationHttpMethod = ConfigurationHttpMethod::createFromArray(
            $httpMethods
        );

        $this->assertEquals($httpMethods, $configurationHttpMethod->toArray());
    }

    /**
     * @dataProvider valuesDataProvider
     *
     * @param array $httpMethods
     */
    public function testCanCasToToString(array $httpMethods)
    {
        $httpMethod = ConfigurationHttpMethod::createFromArray(
            $httpMethods
        );

        $string = implode('|', $httpMethods);
        $this->assertEquals($string, $httpMethod->toString());
    }
}