<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteNameResolverTest extends MockeryTestCase
{
    public function testCanRegisterChainToAnyRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);


        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    '*'
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
            $factory,
            $router
        );

        $this->assertEmpty($resolver->exportRules());
        $resolver->registerRouteMiddleware('*', $middleware1);
    }

    public function testCanRegisterChainToAStandardRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);

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

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameResolver(
            $factory,
            $router
        );

        $this->assertEmpty($resolver->exportRules());
        $resolver->registerRouteMiddleware('test', $middleware1);
    }

    public function testWillDetectNonExistingRouteOnRegistration()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware2
            ->shouldNotReceive('listChainClassNames');


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
            $factory,
            $router
        );

        $this->assertEmpty($resolver->exportRules());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route with name: [test] is not registered");
        $resolver->registerRouteMiddleware('test', $middleware1);
        $this->assertEmpty($resolver->exportRules());
    }

    public function testCanResolveStandardRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $routeName   = 'test';

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    $routeName
                ]
            ]
        );

        $expectedMiddlewareChain = ResolvedMiddlewareChain::createFromResolverContext(
            $middleware1,
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

        $resolver = new RouteNameResolver(
            $factory,
            $router
        );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolver->registerRouteMiddleware($routeName, $middleware1);
        $resolvedMiddlewareChain = $resolver->handle(
            $middlewareResolvingRequest
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testCanResolveMagicRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $routeName   = '*';

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    $routeName
                ]
            ]
        );

        $expectedMiddlewareChain = ResolvedMiddlewareChain::createFromResolverContext(
            $middleware1,
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
            $factory,
            $router
        );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            $routeName
        );

        $resolver->registerRouteMiddleware($routeName, $middleware1);
        $resolvedMiddlewareChain = $resolver->handle(
            $middlewareResolvingRequest
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testCanHandleResolvingToNextResolver()
    {
        /** @var MockInterface|NullMiddleware $middleware1 */
        $middleware = Mockery::mock(NullMiddleware::class);

        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);


        $routeName = 'testing';

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath'    => [
                    $routeName
                ]
            ]
        );

        $expectedMiddlewareChain = ResolvedMiddlewareChain::createFromResolverContext(
            $middleware,
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
            $factory,
            $router
        );

        /** @var AbstractChainResolver|MockInterface $handler */
        $handler = Mockery::mock(AbstractChainResolver::class);
        $handler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $middlewareResolvingRequest
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
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

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
                    $routeName
                ]
            )
            ->andReturn(
                true
            );
        $routeCollection
            ->shouldReceive('get')
            ->times(1)
            ->withArgs(
                [
                    $routeName
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
            $factory,
            $router
        );

        $resolver->registerRouteMiddleware($routeName, $middleware);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route: [{$routeName}] is not registered");
        $resolver->exportRules();
    }

    public function testCanDelegateGettingOfTheChainToNextHandler()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

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
            $middlewareChainItemFactory,
            $router
        );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath' => [
                    '1',
                    '2'
                ],
            ]
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $expectedMiddlewareChain */
        $expectedMiddlewareChain = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractChainResolver $handler */
        $handler = Mockery::mock(AbstractChainResolver::class);
        $handler
            ->shouldReceive(
                'getChain'
            )
            ->once()
            ->withArgs(
                [
                    $accessKey
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
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

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
            $middlewareChainItemFactory,
            $router
        );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath' => [
                    '1',
                    '2'
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
        /** @var MockInterface|NullMiddleware $nullMiddleware */
        $nullMiddleware = Mockery::mock(NullMiddleware::class);

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);
        $middlewareChainItemFactory
            ->shouldReceive('createNullChainItem')
            ->once()
            ->andReturn(
                $nullMiddleware
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
            $middlewareChainItemFactory,
            $router
        );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            'test'
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        $this->assertTrue($resolvedMiddlewareChain->getMiddlewareChain() === $nullMiddleware);
        $this->assertNull($resolvedMiddlewareChain->getMiddlewareAccessKey());
    }
}