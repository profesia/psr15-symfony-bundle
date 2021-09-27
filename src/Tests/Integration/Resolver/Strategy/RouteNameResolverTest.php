<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\Resolver\Strategy;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\InvalidAccessKeyException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteNameResolverTest extends MockeryTestCase
{
    public function testCanExport()
    {
        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $router
        );

        $this->assertEmpty($resolver->exportRules());

        $nullMiddleware = new NullMiddleware();


        $route = new Route(
            '/test1',
            [],
            [],
            [],
            '',
            [],
            [
                'GET',
                'POST',
            ]
        );

        $routeCollection
            ->shouldReceive('get')
            ->times(2)
            ->withArgs(
                [
                    'test1',
                ]
            )->andReturn(
                $route
            );

        $resolver->registerRouteMiddleware(
            'test1',
            new MiddlewareCollection(
                [$nullMiddleware]
            )
        );
        $exportedRules = $resolver->exportRules();
        $this->assertNotEmpty($exportedRules);
        $this->assertCount(1, $exportedRules);

        /** @var ExportedMiddleware $exportedMiddleware */
        $exportedMiddleware = current($exportedRules);

        $this->assertEquals('test1', $exportedMiddleware->getIdentifier());
        $this->assertEquals('GET|POST', $exportedMiddleware->getHttpMethods()->listMethods('|'));

        $route = new Route(
            '/test2',
            [],
            [],
            [],
            '',
            [],
            [
                'GET',
                'POST',
            ]
        );

        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    'test2',
                ]
            )->andReturn(
                $route
            );
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    'test2',
                ]
            )->andReturn(
                null
            );

        $resolver->registerRouteMiddleware(
            'test2',
            new MiddlewareCollection(
                [$nullMiddleware]
            )
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route: [test2] is not registered');
        $resolver->exportRules();
    }

    public function getChainDataProvider()
    {
        $class          = RouteNameResolver::class;
        $nullMiddleware = new NullMiddleware();

        return [
            [
                InvalidAccessKeyException::class,
                "Bad access keys: [1, 2] in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            1,
                            2,
                        ],
                    ]
                ),
                [

                ],
            ],
            [
                InvalidAccessKeyException::class,
                "Bad access keys: [] in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [],
                    ]
                ),
                [

                ],
            ],
            [
                ChainNotFoundException::class,
                "Chain with key: [test] was not found in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            'test',
                        ],
                    ]
                ),
                [
                    [
                        'routeName' => 'testing',
                        'chain'     => new MiddlewareCollection([$nullMiddleware]),
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getChainDataProvider
     *
     * @param string                      $exceptionClass
     * @param string                      $exceptionMessage
     * @param ResolvedMiddlewareAccessKey $accessKey
     * @param array                       $rulesToRegister
     */
    public function testCanThrowExceptionDuringGettingOfChain(
        string $exceptionClass,
        string $exceptionMessage,
        ResolvedMiddlewareAccessKey $accessKey,
        array $rulesToRegister
    ) {
        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);


        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $router
        );

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);

        foreach ($rulesToRegister as $rule) {
            /** @var MockInterface|Route $route */
            $route = Mockery::mock(Route::class);

            $routeCollection
                ->shouldReceive('get')
                ->once()
                ->withArgs(
                    [
                        $rule['routeName'],
                    ]
                )
                ->andReturn(
                    $route
                );

            $resolver->registerRouteMiddleware(
                $rule['routeName'],
                $rule['chain']
            );
        }
        $resolver->getChain($accessKey);
    }

    public function testCanGetMiddlewareChain()
    {
        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    'test',
                ]
            )->andReturn(
                $route
            );

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $router
        );

        $nullMiddleware       = new NullMiddleware();
        $middlewareCollection = new MiddlewareCollection(
            [
                $nullMiddleware,
            ]
        );

        $resolver->registerRouteMiddleware(
            'test',
            $middlewareCollection,
        );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    'test',
                ],
            ]
        );

        $this->assertTrue($middlewareCollection === $resolver->getChain($accessKey));
    }

    public function testWillIgnoreRulesAfterMagicRuleRegistration()
    {
        $routeName = 'test';

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $routeName,
                ]
            )->andReturn(
                $route
            );

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $router
        );

        $middleware1 = new TestMiddleware1();
        $middleware2 = new TestMiddleware2();

        $resolver->registerRouteMiddleware('*', new MiddlewareCollection([$middleware1]));
        $resolver->registerRouteMiddleware($routeName, new MiddlewareCollection([$middleware2]));

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        //$this->assertTrue($resolvedMiddlewareChain->getMiddlewareChain() === $middleware1);
    }

    public function testWillNotOverwriteMagicRule()
    {
        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $router
        );

        $middleware1 = new TestMiddleware1();
        $middleware2 = new TestMiddleware2();

        $resolver->registerRouteMiddleware('*', new MiddlewareCollection([$middleware1]));
        $resolver->registerRouteMiddleware('*', new MiddlewareCollection([$middleware2]));

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            'test'
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
    }

    public function testWillIgnoreDuplicityRules()
    {
        $routeName = 'test';

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $routeName,
                ]
            )->andReturn(
                $route
            );

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $router
        );

        $middleware1 = new TestMiddleware1();
        $middleware2 = new TestMiddleware2();

        $resolver->registerRouteMiddleware($routeName, new MiddlewareCollection([$middleware1]));
        $resolver->registerRouteMiddleware($routeName, new MiddlewareCollection([$middleware2]));

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
    }
}
