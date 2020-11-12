<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Decorator;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Decorator\MiddlewareResolverCacheRemoval;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

class MiddlewareResolverCacheRemovalTest extends MockeryTestCase
{
    public function testWillClearCacheBeforeDelegatingCallToDecoratedObject()
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST'
            ]
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/test'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            'test'
        );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddleware */
        $expectedMiddlewareChain = Mockery::mock(ResolvedMiddlewareChain::class);

        /** @var MockInterface|MiddlewareResolverInterface $decoratedObject */
        $decoratedObject = Mockery::mock(MiddlewareResolverInterface::class);
        $decoratedObject
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                [
                    $middlewareResolvingRequest
                ]
            )->andReturn(
                $expectedMiddlewareChain
            );


        /** @var MockInterface|CacheItemPoolInterface $cacheItemPool */
        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->shouldReceive('deleteItem')
            ->once()
            ->withArgs(
                [
                    $middlewareResolvingRequest->getCacheKey()
                ]
            );

        $decorator = new MiddlewareResolverCacheRemoval(
            $decoratedObject,
            $cacheItemPool
        );

        $resolvedMiddlewareChain = $decorator->resolveMiddlewareChain(
            $middlewareResolvingRequest
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }
}