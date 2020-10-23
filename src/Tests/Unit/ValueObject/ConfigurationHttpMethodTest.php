<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\ValueObject\AbstractHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class ConfigurationHttpMethodTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanAssignMiddlewareChainToAnyHttpMethod()
    {
        $httpMethod = ConfigurationHttpMethod::createDefault();

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        $returnValue = $httpMethod->assignMiddlewareChainToHttpMethods($middleware);

        $allHttpMethods = AbstractHttpMethod::getPossibleValues();
        $this->assertCount(count($allHttpMethods), $returnValue);
        foreach ($allHttpMethods as $httpMethod) {
            $this->assertEquals($returnValue[$httpMethod], $middleware);
        }
    }

    public function testCanAssignMiddlewareChainToSpecificHttpMethod()
    {
        $httpMethod = ConfigurationHttpMethod::createFromString('GET');

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware  = Mockery::mock(AbstractMiddlewareChainItem::class);
        $returnValue = $httpMethod->assignMiddlewareChainToHttpMethods($middleware);

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
        $returnValue    = $httpMethod->assignMiddlewareChainToHttpMethods($middleware);
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

    public function testCanHandleAnyKeywordInMultipleInputString()
    {
        $httpMethods         = [
            'GET',
            'POST',
            'ANY',
            'PUT'
        ];

        $implodedHttpMethods = implode('|', $httpMethods);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("HTTP method configuration is not valid. In case of 'ANY' any other HTTP methods are redundant");
        ConfigurationHttpMethod::createFromString($implodedHttpMethods);
    }

    public function testCanHandleUnsupportedHttpMethodInInputString()
    {
        $unsupportedString = 'assdaads';
        $httpMethods         = [
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