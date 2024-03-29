<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\Console\Command;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Console\Command\WarmUpMiddlewareCacheCommand;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverCachingInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class WarmUpMiddlewareCacheCommandTest extends MockeryTestCase
{
    public function testCanHandleNonNullMiddlewares()
    {
        $httpMethods = [
            'route1' => [
                'GET',
                'POST'
            ],
            'route2' => []
        ];
        $route1 = static::createRouteMock(
            '/route1',
            $httpMethods['route1']
        );


        $route2 = static::createRouteMock(
            '/route2',
            $httpMethods['route2']
        );

        $routes =                 [
            'route1' => $route1,
            'route2' => $route2,
        ];

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('all')
            ->once()
            ->andReturn(
                $routes
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $middlewareChains = [
            'route1' => new MiddlewareCollection(
                [
                    new TestMiddleware1(),
                    new TestMiddleware2(),
                ]
            ),
            'route2' => new MiddlewareCollection(
                [
                    new TestMiddleware1(),
                    new TestMiddleware2(),
                    new TestMiddleware2(),
                ]
            ),
        ];

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'accessPath' => [
                    'a',
                    'b'
                ],
                'resolverClass' => CompiledPathResolver::class
            ]
        );

        /** @var MockInterface|MiddlewareResolverCachingInterface $resolverCacheProxy */
        $resolverCacheProxy = Mockery::mock(MiddlewareResolverCachingInterface::class);
        foreach ($routes as $routeName => $route) {
            $resolverCacheProxy
                ->shouldReceive('resolveMiddlewareChain')
                ->times($httpMethods[$routeName] === [] ? sizeof(HttpMethod::getPossibleValues()): sizeof($httpMethods[$routeName]))
                ->withArgs(
                    function (MiddlewareResolvingRequest $request) use ($routeName) {
                        if ($request->getRouteName() !== $routeName) {
                            return false;
                        }

                        return true;
                    }
                )
                ->andReturn(
                    ResolvedMiddlewareChain::createFromResolverContext(
                        $middlewareChains[$routeName],
                        $accessKey
                    )
                );
        }

        $command = new WarmUpMiddlewareCacheCommand(
            $router,
            $resolverCacheProxy
        );

        $tester = new CommandTester(
            $command
        );

        $statusCode = $tester->execute([]);
        $this->assertEquals(1, $statusCode);
    }

    private static function createRouteMock(string $path, array $methods): Route
    {
        return (new Route(
            $path
        ))->setMethods(
            $methods
        );
    }
}
