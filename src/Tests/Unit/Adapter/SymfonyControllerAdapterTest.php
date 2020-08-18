<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\Adapter;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Delvesoft\Symfony\Psr15Bundle\RequestHandler\Factory\SymfonyControllerRequestHandlerFactory;
use Delvesoft\Symfony\Psr15Bundle\RequestHandler\SymfonyControllerRequestHandler;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SymfonyControllerAdapterTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanHandle()
    {
        /** @var MockInterface|Request $request */
        $request = Mockery::mock(Request::class);

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

        /** @var MockInterface|RequestMiddlewareResolverInterface $middlewareResolver */
        $middlewareResolver = Mockery::mock(RequestMiddlewareResolverInterface::class);
        $middlewareResolver
            ->shouldReceive('resolveMiddlewareChain')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )
            ->andReturn(
                $middleware
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

        $adapter = new SymfonyControllerAdapter(
            $middlewareResolver,
            $foundationFactory,
            $psrMessageFactory,
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