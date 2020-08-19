<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\Resolver\Decorator;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Decorator\RequestMiddlewareResolverCacheRemoval;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestMiddlewareResolverCacheRemovalTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testWillClearCacheBeforeDelegatingCallToDecoratedObject()
    {
        /** @var MockInterface|Request $request */
        $request = Mockery::mock(Request::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $expectedMiddleware */
        $expectedMiddleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|RequestMiddlewareResolverInterface $decoratedObject */
        $decoratedObject = Mockery::mock(RequestMiddlewareResolverInterface::class);
        $decoratedObject
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )->andReturn(
                $expectedMiddleware
            );


        /** @var MockInterface|CacheItemPoolInterface $cacheItemPool */
        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->shouldReceive('clear')
            ->once();

        $decorator = new RequestMiddlewareResolverCacheRemoval(
            $decoratedObject,
            $cacheItemPool
        );

        $resolvedMiddleware = $decorator->resolveMiddlewareChain(
            $request
        );

        $this->assertEquals($expectedMiddleware, $resolvedMiddleware);
    }
}