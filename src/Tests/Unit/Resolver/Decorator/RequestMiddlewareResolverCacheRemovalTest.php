<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Decorator;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Resolver\Decorator\RequestMiddlewareResolverCacheRemoval;
use Profesia\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestMiddlewareResolverCacheRemovalTest extends MockeryTestCase
{
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