<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Adapter;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Profesia\Symfony\Psr15Bundle\RequestHandler\Factory\SymfonyControllerRequestHandlerFactory;
use Profesia\Symfony\Psr15Bundle\RequestHandler\SymfonyControllerRequestHandler;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use RuntimeException;

class SymfonyControllerAdapterTest extends MockeryTestCase
{
    public function testCanDetectNonExistingRoute()
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

        /** @var MockInterface|MiddlewareResolverInterface $middlewareResolver */
        $middlewareResolver = Mockery::mock(MiddlewareResolverInterface::class);

        /** @var MockInterface|HttpFoundationFactoryInterface $foundationFactory */
        $foundationFactory = Mockery::mock(HttpFoundationFactoryInterface::class);

        /** @var MockInterface|HttpMessageFactoryInterface $psrMessageFactory */
        $psrMessageFactory = Mockery::mock(HttpMessageFactoryInterface::class);

        /** @var MockInterface|SymfonyControllerRequestHandlerFactory $requestHandlerFactory */
        $requestHandlerFactory = Mockery::mock(SymfonyControllerRequestHandlerFactory::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
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

        $adapter = new SymfonyControllerAdapter(
            $middlewareResolver,
            $foundationFactory,
            $psrMessageFactory,
            $router,
            $requestHandlerFactory
        );

        $originalController = function (Request $request, string $content)  {
            return null;
        };

        $adapter->setOriginalResources(
            $originalController,
            $request,
            [
                0 => $request,
            ]
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route: [{$routeName}] is not registered");
        $adapter->__invoke();
    }

    public function testCanHandle()
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

        /** @var MockInterface|ServerRequestInterface $psrRequest */
        $psrRequest = Mockery::mock(ServerRequestInterface::class);

        /** @var MockInterface|ResponseInterface $psrResponse */
        $psrResponse = Mockery::mock(ResponseInterface::class);

        $content     = 'Testing';
        $statusCode  = 201;
        $contentType = 'abcd';
        $headers     = [
            'Content-Type' => $contentType
        ];

        $response = new Response(
            $content,
            $statusCode,
            $headers
        );

        /** @var MockInterface|SymfonyControllerRequestHandler $requestHandler */
        $requestHandler = Mockery::mock(SymfonyControllerRequestHandler::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware
            ->shouldReceive('process')
            ->withArgs(
                [
                    $psrRequest,
                    $requestHandler
                ]
            )
            ->andReturn(
                $psrResponse
            );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddlewareChain */
        $resolvedMiddlewareChain = Mockery::mock(ResolvedMiddlewareChain::class);
        $resolvedMiddlewareChain
            ->shouldReceive('getMiddlewareChain')
            ->once()
            ->andReturn(
                $middleware
            );

        /** @var MockInterface|MiddlewareResolverInterface $middlewareResolver */
        $middlewareResolver = Mockery::mock(MiddlewareResolverInterface::class);
        $middlewareResolver
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                function (MiddlewareResolvingRequest $request) use ($routeName) {
                    if ($request->hasAccessKey()) {
                        return false;
                    }

                    $httpMethod = $request->getHttpMethod();
                    if (!$httpMethod->equals(HttpMethod::createFromString('POST'))) {
                        return false;
                    }
                    
                    if ($request->getRouteName() !== $routeName) {
                        return false;
                    }

                    return true;
                }
            )
            ->andReturn(
                $resolvedMiddlewareChain
            );

        /** @var MockInterface|HttpFoundationFactoryInterface $foundationFactory */
        $foundationFactory = Mockery::mock(HttpFoundationFactoryInterface::class);
        $foundationFactory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    $psrResponse
                ]
            )
            ->andReturn(
                $response
            );

        /** @var MockInterface|HttpMessageFactoryInterface $psrMessageFactory */
        $psrMessageFactory = Mockery::mock(HttpMessageFactoryInterface::class);
        $psrMessageFactory
            ->shouldReceive('createRequest')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )
            ->andReturn(
                $psrRequest
            );

        $originalController = function (Request $request, string $content) use ($response) {
            return $response;
        };

        /** @var MockInterface|SymfonyControllerRequestHandlerFactory $requestHandlerFactory */
        $requestHandlerFactory = Mockery::mock(SymfonyControllerRequestHandlerFactory::class);
        $requestHandlerFactory
            ->shouldReceive('create')
            ->once()
            ->withArgs(
                [
                    $originalController,
                    [
                        0 => $request,
                        1 => $content
                    ]
                ]
            )->andReturn(
                $requestHandler
            );

        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/test'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('compile')
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
            )
            ->andReturn(
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

        $adapter = new SymfonyControllerAdapter(
            $middlewareResolver,
            $foundationFactory,
            $psrMessageFactory,
            $router,
            $requestHandlerFactory
        );


        $adapter->setOriginalResources(
            $originalController,
            $request,
            [
                0 => $request,
                1 => $content
            ]
        );

        $response = $adapter->__invoke();
        $this->assertEquals($content, $response->getContent());
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertEquals($contentType, $response->headers->get('Content-Type'));
    }
}