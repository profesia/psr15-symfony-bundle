<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\Resolver\Strategy;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathStrategyResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CompiledPathStrategyResolverTest extends MockeryTestCase
{
    public function testCanCheckMultipleRegisteredRulesTillTheMatchingOneIsFound()
    {
        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

        /** @var MockInterface|HttpMethod $httpMethod */
        $httpMethod = Mockery::mock(HttpMethod::class);
        $httpMethod
            ->shouldReceive('extractMiddleware')
            ->once()
            ->withArgs(
                [
                    [
                        'GET'  => $nullMiddleware,
                        'POST' => $nullMiddleware,
                    ]
                ]
            )->andReturn(
                $nullMiddleware
            );

        $request = new MiddlewareResolvingRequest(
            $httpMethod,
            'test'
        );

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/12'
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
                    $request->getRouteName()
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

        $resolver = new CompiledPathStrategyResolver(
            $middlewareChainItemFactory,
            $router
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1',
            ),
            [
                'GET' => $nullMiddleware,
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/12',
            ),
            [
                'GET'  => $nullMiddleware,
                'POST' => $nullMiddleware,
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/123',
            ),
            [
                'GET'  => $nullMiddleware,
                'POST' => $nullMiddleware,
                'PUT'  => $nullMiddleware,
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1234',
            ),
            [
                'GET'    => $nullMiddleware,
                'POST'   => $nullMiddleware,
                'PUT'    => $nullMiddleware,
                'DELETE' => $nullMiddleware,
            ]
        );

        $this->assertEquals($nullMiddleware, $resolver->handle($request));
    }

    public function testCanExport()
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

        $resolver = new CompiledPathStrategyResolver(
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

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET|POST'),
                '/sk',
            ),
            [
                'GET'  => $nullMiddleware,
                'POST' => $nullMiddleware,
            ]
        );

        $exportedRules = $resolver->exportRules();
        $this->assertNotEmpty($exportedRules);
        $this->assertCount(1, $exportedRules);

        /** @var ExportedMiddleware $exportedMiddleware */
        $exportedMiddleware = current($exportedRules);
        $this->assertEquals('/sk', $exportedMiddleware->getIdentifier());
        $this->assertEquals('GET|POST', $exportedMiddleware->getHttpMethods()->listMethods('|'));
    }
}