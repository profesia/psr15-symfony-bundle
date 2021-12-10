<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Decorator;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Decorator\MiddlewareResolverCaching;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

class MiddlewareResolverCachingTest extends MockeryTestCase
{

    public function testWillUpdateRequestOnCachedAccessKey()
    {
        $routeName        = 'test';
        $staticPrefix     = 'static-prefix';
        $httpMethodString = 'POST';
        $request          = new Request(
            [],
            [],
            [
                '_route' => $routeName
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethodString
            ]
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
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

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );

        $key        = urlencode("{$httpMethodString}-{$staticPrefix}");
        $accessPath = [
            1,
            2
        ];

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => $accessPath,
            ]
        );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddlewareChain */
        $resolvedMiddlewareChain = Mockery::mock(ResolvedMiddlewareChain::class);
        $resolvedMiddlewareChain
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                true
            );

        /** @var MockInterface|MiddlewareResolverInterface $resolver */
        $resolver = Mockery::mock(MiddlewareResolverInterface::class);
        $resolver
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                function (MiddlewareResolvingRequest $innerRequest) use ($middlewareResolvingRequest, $accessKey, $routeName, $httpMethodString) {
                    if (!$innerRequest->hasAccessKey()) {
                        return false;
                    }

                    if ($innerRequest->getAccessKey()->toArray() !== $accessKey->toArray()) {
                        return false;
                    }

                    if ($innerRequest->getRouteName() !== $routeName) {
                        return false;
                    }

                    if (!$innerRequest->getHttpMethod()->equals(HttpMethod::createFromString($httpMethodString))) {
                        return false;
                    }

                    return true;
                }
            )->andReturn(
                $resolvedMiddlewareChain
            );

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
                $accessKey->toArray()
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


        $proxy = new MiddlewareResolverCaching(
            $resolver,
            $cacheItemPool
        );

        $proxy->resolveMiddlewareChain($middlewareResolvingRequest);
    }

    public function testWillSaveResolvedMiddlewareToCache()
    {
        $routeName        = 'test';
        $staticPrefix     = 'static-prefix';
        $httpMethodString = 'POST';
        $request          = new Request(
            [],
            [],
            [
                '_route' => $routeName
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethodString
            ]
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
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

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );

        $key       = urlencode("{$httpMethodString}-{$staticPrefix}");
        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    1,
                    2
                ],
            ]
        );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddlewareChain */
        $resolvedMiddlewareChain = Mockery::mock(ResolvedMiddlewareChain::class);
        $resolvedMiddlewareChain
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );
        $resolvedMiddlewareChain
            ->shouldReceive('getMiddlewareAccessKey')
            ->once()
            ->andReturn($accessKey);

        /** @var MockInterface|MiddlewareResolverInterface $resolver */
        $resolver = Mockery::mock(MiddlewareResolverInterface::class);
        $resolver
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                function (MiddlewareResolvingRequest $innerRequest) use ($middlewareResolvingRequest, $routeName, $httpMethodString) {
                    if ($innerRequest->hasAccessKey()) {
                        return false;
                    }

                    if ($innerRequest->getRouteName() !== $routeName) {
                        return false;
                    }

                    if (!$innerRequest->getHttpMethod()->equals(HttpMethod::createFromString($httpMethodString))) {
                        return false;
                    }

                    return true;
                }
            )->andReturn(
                $resolvedMiddlewareChain
            );

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
                    $accessKey->toArray()
                ]
            )->andReturnSelf();

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


        $proxy = new MiddlewareResolverCaching(
            $resolver,
            $cacheItemPool
        );

        $proxy->resolveMiddlewareChain($middlewareResolvingRequest);
    }

    public function testWillNotSaveResolvedMiddlewareToCacheWhenMiddlewareWasFetchedFromCache()
    {
        $routeName        = 'test';
        $staticPrefix     = 'static-prefix';
        $httpMethodString = 'POST';
        $request          = new Request(
            [],
            [],
            [
                '_route' => $routeName
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethodString
            ]
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
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

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );

        $key        = urlencode("{$httpMethodString}-{$staticPrefix}");
        $accessPath = [
            1,
            2
        ];

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => $accessPath,
            ]
        );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddlewareChain */
        $resolvedMiddlewareChain = Mockery::mock(ResolvedMiddlewareChain::class);
        $resolvedMiddlewareChain
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );

        /** @var MockInterface|MiddlewareResolverInterface $resolver */
        $resolver = Mockery::mock(MiddlewareResolverInterface::class);
        $resolver
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                function (MiddlewareResolvingRequest $innerRequest) use ($middlewareResolvingRequest, $accessKey, $routeName, $httpMethodString) {
                    if (!$innerRequest->hasAccessKey()) {
                        return false;
                    }

                    if ($innerRequest->getAccessKey()->toArray() !== $accessKey->toArray()) {
                        return false;
                    }

                    if ($innerRequest->getRouteName() !== $routeName) {
                        return false;
                    }

                    if (!$innerRequest->getHttpMethod()->equals(HttpMethod::createFromString($httpMethodString))) {
                        return false;
                    }

                    return true;
                }
            )->andReturn(
                $resolvedMiddlewareChain
            );

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
                $accessKey->toArray()
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


        $proxy = new MiddlewareResolverCaching(
            $resolver,
            $cacheItemPool
        );

        $proxy->resolveMiddlewareChain($middlewareResolvingRequest);
    }
}
