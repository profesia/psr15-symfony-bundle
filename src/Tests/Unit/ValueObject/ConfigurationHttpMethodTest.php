<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\AbstractHttpMethod;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

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
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        $returnValue = $httpMethod->assignMiddlewareChainToHttpMethods($middleware);

        $this->assertCount(1, $returnValue);
        $this->assertEquals($returnValue['GET'], $middleware);
    }
}