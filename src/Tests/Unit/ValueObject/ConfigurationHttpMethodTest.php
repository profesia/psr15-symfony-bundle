<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Psr\Http\Server\MiddlewareInterface;

class ConfigurationHttpMethodTest extends MockeryTestCase
{
    public static function valuesDataProvider(): array
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

        /** @var MockInterface|MiddlewareInterface $middleware */
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $allHttpMethods = HttpMethod::getPossibleValues();
        $middlewares    = [];
        foreach ($allHttpMethods as $method) {
            $middlewares[$method] = new MiddlewareCollection([$middleware]);
        }

        $returnValue = $httpMethod->assignMiddlewareChainToHttpMethods($middlewares);

        $this->assertCount(count($allHttpMethods), $returnValue);
        foreach ($allHttpMethods as $method) {
            $this->assertEquals(new MiddlewareCollection([$middleware]), $returnValue[$method]);
        }
    }

    public function testCanAssignMiddlewareChainToSpecificHttpMethod()
    {
        $httpMethod = ConfigurationHttpMethod::createFromString('GET');

        /** @var MockInterface|MiddlewareInterface $middleware */
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $middlewareCollection = new MiddlewareCollection([$middleware]);
        $middlewares = [
            'GET' => $middlewareCollection
        ];
        $returnValue = $httpMethod->assignMiddlewareChainToHttpMethods($middlewares);

        $this->assertCount(1, $returnValue);
        $this->assertEquals($returnValue['GET'], $middlewareCollection);
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

        /** @var MockInterface|MiddlewareInterface $middleware */
        $middleware = Mockery::mock(MiddlewareInterface::class);
        $middlewares = [];
        foreach ($httpMethods as $method) {
            $middlewares[$method] = new MiddlewareCollection([$middleware]);
        }

        $returnValue    = $httpMethod->assignMiddlewareChainToHttpMethods($middlewares);
        $string         = $httpMethod->toString();
        $explodedString = explode('|', $string);

        $this->assertEquals($implodedHttpMethods, $string);
        $this->assertCount(3, $returnValue);

        $index = 0;
        foreach ($httpMethods as $httpMethod) {
            $this->assertEquals(new MiddlewareCollection([$middleware]), $returnValue[$httpMethod]);
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

        /** @var MockInterface|MiddlewareInterface $middleware */
        $middleware = Mockery::mock(MiddlewareInterface::class);

        $middlewares = [
            'GET'  => new MiddlewareCollection([$middleware]),
            'POST' => new MiddlewareCollection([$middleware]),
            'PUT'  => new MiddlewareCollection([$middleware])
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
    public function testCanCastToToString(array $httpMethods)
    {
        $httpMethod = ConfigurationHttpMethod::createFromArray(
            $httpMethods
        );

        $string = implode('|', $httpMethods);
        $this->assertEquals($string, $httpMethod->toString());
    }
}
