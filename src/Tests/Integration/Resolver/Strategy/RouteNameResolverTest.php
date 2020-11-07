<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\InvalidAccessKeyException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteNameResolverTest extends MockeryTestCase
{
    public function testCanExport()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

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
            $middlewareChainItemFactory,
            $router
        );

        $this->assertEmpty($resolver->exportRules());

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );


        $route = new Route(
            '/test1',
            [],
            [],
            [],
            '',
            [],
            [
                'GET',
                'POST'
            ]
        );

        $routeCollection
            ->shouldReceive('get')
            ->times(2)
            ->withArgs(
                [
                    'test1'
                ]
            )->andReturn(
                $route
            );

        $resolver->registerRouteMiddleware(
            'test1',
            $nullMiddleware
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
                'POST'
            ]
        );

        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    'test2'
                ]
            )->andReturn(
                $route
            );
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    'test2'
                ]
            )->andReturn(
                null
            );

        $resolver->registerRouteMiddleware(
            'test2',
            $nullMiddleware
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Route: [test2] is not registered');
        $resolver->exportRules();
    }

    public function getChainDataProvider()
    {
        $class = RouteNameResolver::class;

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

        return [
            [
                InvalidAccessKeyException::class,
                "Bad access keys: [1, 2] in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            1,
                            2
                        ]
                    ]
                ),
                [

                ]
            ],
            [
                InvalidAccessKeyException::class,
                "Bad access keys: [] in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => []
                    ]
                ),
                [

                ]
            ],
            [
                ChainNotFoundException::class,
                "Chain with key: [test] was not found in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            'test'
                        ]
                    ]
                ),
                [
                    [
                        'routeName' => 'testing',
                        'chain'     => $nullMiddleware,
                    ]
                ]
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
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

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
            $middlewareChainItemFactory,
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
                        $rule['routeName']
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
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    'test'
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
            $middlewareChainItemFactory,
            $router
        );

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

        $resolver->registerRouteMiddleware(
            'test',
            $nullMiddleware,
        );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    'test'
                ],
            ]
        );

        $this->assertTrue($nullMiddleware === $resolver->getChain($accessKey));
    }

    public function testWillIgnoreRulesAfterMagicRuleRegistration()
    {
        $routeName = 'test';

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

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

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $middlewareChainItemFactory,
            $router
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);

        $resolver->registerRouteMiddleware('*', $middleware1);
        $resolver->registerRouteMiddleware($routeName, $middleware2);

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->getMiddlewareChain() === $middleware1);
    }

    public function testWillNotOverwriteMagicRule()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

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
            $middlewareChainItemFactory,
            $router
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);

        $resolver->registerRouteMiddleware('*', $middleware1);
        $resolver->registerRouteMiddleware('*', $middleware2);

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            'test'
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->getMiddlewareChain() === $middleware1);
    }

    public function testWillIgnoreDuplicityRules()
    {
        $routeName = 'test';

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

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

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $middlewareChainItemFactory,
            $router
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);

        $resolver->registerRouteMiddleware($routeName, $middleware1);
        $resolver->registerRouteMiddleware($routeName, $middleware2);

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->getMiddlewareChain() === $middleware1);
    }
}