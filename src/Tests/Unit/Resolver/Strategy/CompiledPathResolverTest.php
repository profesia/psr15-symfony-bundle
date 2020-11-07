<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use RuntimeException;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CompiledPathResolverTest extends MockeryTestCase
{
    /*public function testWillAppendNewRuleToExistingRuleBasedOnHttpMethod()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        /*$middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        /*$routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
        /*$router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        /*$middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        /*$middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware1
            ->shouldReceive('append')
            ->once()
            ->withArgs(
                [
                    $middleware2
                ]
            )->andReturn(
                $middleware1
            );

        $configurationPath = ConfigurationPath::createFromConfigurationHttpMethodAndString(
            ConfigurationHttpMethod::createFromString('POST'),
            '/test'
        );

        $middlewares = [
            'POST' => $middleware1
        ];
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );

        $middlewares['POST'] = $middleware2;
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );

        $middlewares['GET'] = $middleware2;
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );
    }

    public function testCanHandleNonExistingRoute()
    {
        /*$request = new MiddlewareResolvingRequest(
            HttpMethod::createFromString('POST'),
            'test'
        );*/

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        /*$middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        /*$routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $request->getRouteName()
                ]
            )->andReturn(
                null
            );

        /** @var MockInterface|RouterInterface $router */
        /*$router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );


        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route: [{$request->getRouteName()}] is not registered");
        $resolver->handle($request);
    }

    public function testWillHandleRouteCompilation()
    {
        /*$request = new MiddlewareResolvingRequest(
            HttpMethod::createFromString('POST'),
            'test'
        );*/

        /** @var MockInterface|NullMiddleware $nullMiddleware */
        /*$nullMiddleware = Mockery::mock(NullMiddleware::class);

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        /*$middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);
        $middlewareChainItemFactory
            ->shouldReceive('createNullChainItem')
            ->once()
            ->andReturn(
                $nullMiddleware
            );

        /** @var MockInterface|CompiledRoute $compiledRoute */
        /*$compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/test'
            );

        /** @var MockInterface|Route $route */
        /*$route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        /** @var MockInterface|RouteCollection $routeCollection */
        /*$routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $request->getRouteName()
                ]
            )->andReturn(
                $route
            );

        /** @var MockInterface|RouterInterface $router */
        /*$router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );


        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

        $resolvedMiddleware = $resolver->handle($request);
        $this->assertEquals($nullMiddleware, $resolvedMiddleware);
    }*/
}