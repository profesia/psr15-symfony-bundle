<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteNameResolverTest extends MockeryTestCase
{
    public function testCanRegisterChainToAnyRoute()
    {
        /** @var MockInterface|MiddlewareInterface $middleware1 */
        $middleware1 = Mockery::mock(MiddlewareInterface::class);

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
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
        $resolver->registerRouteMiddleware('*', new MiddlewareCollection([$middleware1]));
    }

    public function testCanRegisterChainToAStandardRoute()
    {
        /** @var MockInterface|MiddlewareInterface $middleware1 */
        $middleware1 = Mockery::mock(MiddlewareInterface::class);

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

        /** @var MockInterface|RouterInterface $router */
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
        $resolver->registerRouteMiddleware('test', new MiddlewareCollection([$middleware1]));
    }

    public function testWillDetectNonExistingRouteOnRegistration()
    {
        /** @var MockInterface|MiddlewareInterface $middleware1 */
        $middleware1 = Mockery::mock(MiddlewareInterface::class);

        /** @var MockInterface|MiddlewareInterface $middleware2 */
        $middleware2 = Mockery::mock(MiddlewareInterface::class);
        $middleware2
            ->shouldNotReceive('listChainClassNames');


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
                null
            );

        /** @var MockInterface|RouterInterface $router */
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
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route with name: [test] is not registered");
        $resolver->registerRouteMiddleware('test', new MiddlewareCollection([$middleware1]));
        $this->assertEmpty($resolver->exportRules());
    }

    public function testCanResolveStandardRoute()
    {
        $middleware1 = Mockery::mock(MiddlewareInterface::class);
        $routeName   = 'test';

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    $routeName,
                ],
            ]
        );

        $expectedMiddlewareChain = ResolvedMiddlewareChain::createFromResolverContext(
            new MiddlewareCollection([$middleware1]),
            $accessKey
        );

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

        /** @var MockInterface|RouterInterface $router */
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

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolver->registerRouteMiddleware($routeName, new MiddlewareCollection([$middleware1]));
        $resolvedMiddlewareChain = $resolver->handle(
            $middlewareResolvingRequest
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testCanResolveMagicRoute()
    {
        /** @var MockInterface|MiddlewareInterface $middleware1 */
        $middleware1 = Mockery::mock(MiddlewareInterface::class);
        $routeName   = '*';

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    $routeName,
                ],
            ]
        );

        $expectedMiddlewareChain = ResolvedMiddlewareChain::createFromResolverContext(
            new MiddlewareCollection([$middleware1]),
            $accessKey
        );

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
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

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolver->registerRouteMiddleware($routeName, new MiddlewareCollection([$middleware1]));
        $resolvedMiddlewareChain = $resolver->handle(
            $middlewareResolvingRequest
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testCanHandleResolvingToNextResolver()
    {
        /** @var MockInterface|NullMiddleware $middleware1 */
        $middleware = Mockery::mock(NullMiddleware::class);
        $routeName  = 'testing';
        $accessKey  = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    $routeName,
                ],
            ]
        );

        $expectedMiddlewareChain = ResolvedMiddlewareChain::createFromResolverContext(
            new MiddlewareCollection([$middleware]),
            $accessKey
        );

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolver = new RouteNameResolver(
            $router
        );

        /** @var AbstractChainResolver|MockInterface $handler */
        $handler = Mockery::mock(AbstractChainResolver::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $middlewareResolvingRequest,
                ]
            )
            ->andReturn(
                $expectedMiddlewareChain
            );

        $resolver->setNext($handler);


        $resolvedMiddlewareChain = $resolver->handle(
            $middlewareResolvingRequest
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testWillDetectNonExistingRouteDuringExport()
    {
        /** @var MockInterface|NullMiddleware $middleware1 */
        $middleware = Mockery::mock(NullMiddleware::class);

        $routeName = 'testing';

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->times(1)
            ->withArgs(
                [
                    $routeName,
                ]
            )
            ->andReturn(
                new Route('/testing')
            );
        $routeCollection
            ->shouldReceive('get')
            ->times(1)
            ->withArgs(
                [
                    $routeName,
                ]
            )
            ->andReturn(
                null
            );

        /** @var MockInterface|RouterInterface $router */
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

        $resolver->registerRouteMiddleware($routeName, new MiddlewareCollection([$middleware]));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route: [{$routeName}] is not registered");
        $resolver->exportRules();
    }

    public function testCanDelegateGettingOfTheChainToNextHandler()
    {
        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
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

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    '1',
                    '2',
                ],
            ]
        );

        /** @var MockInterface|ResolvedMiddlewareChain $expectedMiddlewareChain */
        $expectedMiddlewareChain = Mockery::mock(ResolvedMiddlewareChain::class);

        /** @var MockInterface|AbstractChainResolver $handler */
        $handler = Mockery::mock(AbstractChainResolver::class);
        $handler
            ->shouldReceive(
                'getChain'
            )
            ->once()
            ->withArgs(
                [
                    $accessKey,
                ]
            )
            ->andReturn(
                $expectedMiddlewareChain
            );

        $resolver->setNext(
            $handler
        );

        $resolvedMiddlewareChain = $resolver->getChain(
            $accessKey
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testWillThrowExceptionOnGettingMiddlewareChainWhenThereIsNoNextResolver()
    {
        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
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

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    '1',
                    '2',
                ],
            ]
        );

        $this->expectException(ChainNotFoundException::class);
        $this->expectExceptionMessage('No resolver was able to retrieve middleware chain');
        $resolver->getChain(
            $accessKey
        );
    }

    public function testWillReturnNullMiddlewareOnNoNextHandlerRegistered()
    {
        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
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

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            'test'
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        $this->assertNull($resolvedMiddlewareChain->getMiddlewareAccessKey());
    }
}
