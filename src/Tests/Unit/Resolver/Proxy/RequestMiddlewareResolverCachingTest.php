<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Proxy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Resolver\Proxy\RequestMiddlewareResolverCaching;
use Profesia\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RequestMiddlewareResolverCachingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanDetectNonExistingRoute()
    {
        $routeName = 'test';
        $request   = new Request(
            [],
            [],
            [
                '_route' => $routeName
            ]
        );


        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $routeName
                ]
            )->andReturn(null);

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        /** @var MockInterface|RequestMiddlewareResolverInterface $resolver */
        $resolver = Mockery::mock(RequestMiddlewareResolverInterface::class);

        /** @var MockInterface|CacheItemPoolInterface $cacheItemPool */
        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);

        $proxy = new RequestMiddlewareResolverCaching(
            $router,
            $resolver,
            $cacheItemPool
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route: [{$routeName}] is not registered");
        $proxy->resolveMiddlewareChain($request);
    }

    public function testWillReturnCachedMiddleware()
    {
        $routeName = 'test';
        $request   = new Request(
            [],
            [],
            [
                '_route' => $routeName
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $staticPrefix = 'static-prefix';

        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                $staticPrefix
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $routeName
                ]
            )->andReturn(
                $route
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        /** @var MockInterface|RequestMiddlewareResolverInterface $resolver */
        $resolver = Mockery::mock(RequestMiddlewareResolverInterface::class);

        $key = urlencode("POST-{$staticPrefix}");

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|CacheItemInterface $cacheItem */
        $cacheItem = Mockery::mock(CacheItemInterface::class);
        $cacheItem
            ->shouldReceive('isHit')
            ->once()
            ->andReturn(true);
        $cacheItem
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                $middleware
            );

        /** @var MockInterface|CacheItemPoolInterface $cacheItemPool */
        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->shouldReceive('getItem')
            ->once()
            ->withArgs(
                [
                    $key
                ]
            )->andReturn(
                $cacheItem
            );


        $proxy = new RequestMiddlewareResolverCaching(
            $router,
            $resolver,
            $cacheItemPool
        );

        $returnedMiddleware = $proxy->resolveMiddlewareChain($request);
        $this->assertEquals($middleware, $returnedMiddleware);
    }

    public function testWillCacheResolvedMiddleware()
    {
        $routeName = 'test';
        $request   = new Request(
            [],
            [],
            [
                '_route' => $routeName
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST'
            ]
        );

        $staticPrefix = 'static-prefix';

        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                $staticPrefix
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $routeName
                ]
            )->andReturn(
                $route
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|RequestMiddlewareResolverInterface $resolver */
        $resolver = Mockery::mock(RequestMiddlewareResolverInterface::class);
        $resolver
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )->andReturn($middleware);

        $key = urlencode("POST-{$staticPrefix}");

        /** @var MockInterface|CacheItemInterface $cacheItem */
        $cacheItem = Mockery::mock(CacheItemInterface::class);
        $cacheItem
            ->shouldReceive('isHit')
            ->once()
            ->andReturn(false);
        $cacheItem
            ->shouldReceive('set')
            ->once()
            ->withArgs(
                [
                    $middleware
                ]
            );

        /** @var MockInterface|CacheItemPoolInterface $cacheItemPool */
        $cacheItemPool = Mockery::mock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->shouldReceive('getItem')
            ->once()
            ->withArgs(
                [
                    $key
                ]
            )->andReturn(
                $cacheItem
            );
        $cacheItemPool
            ->shouldReceive('save')
            ->once()
            ->withArgs(
                [
                    $cacheItem
                ]
            );

        $proxy = new RequestMiddlewareResolverCaching(
            $router,
            $resolver,
            $cacheItemPool
        );

        $returnedMiddleware = $proxy->resolveMiddlewareChain($request);
        $this->assertEquals($middleware, $returnedMiddleware);
    }
}