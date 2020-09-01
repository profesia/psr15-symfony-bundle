<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\MockInterface;

class HttpMethodTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
}