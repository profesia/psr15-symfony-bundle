<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\RequestHandler;

use Mockery;
use Mockery\MockInterface;
use Nyholm\Psr7\Response as PsrResponse;
use Profesia\Symfony\Psr15Bundle\RequestHandler\SymfonyControllerRequestHandler;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SymfonyControllerRequestHandlerTest extends MockeryTestCase
{
    public function testCanHandle()
    {
        /** @var MockInterface|Request $request */
        $request = Mockery::mock(Request::class);

        /** @var MockInterface|ServerRequestInterface $psrRequest */
        $psrRequest = Mockery::mock(ServerRequestInterface::class);

        /** @var MockInterface|Request $request */
        $transformedSymfonyRequest = Mockery::mock(Request::class);

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

        $expectedResponse = new PsrResponse(
            $statusCode,
            $headers,
            $content
        );

        /** @var MockInterface|HttpFoundationFactoryInterface $foundationFactory */
        $foundationFactory = Mockery::mock(HttpFoundationFactoryInterface::class);
        $foundationFactory
            ->shouldReceive('createRequest')
            ->once()
            ->withArgs(
                [
                    $psrRequest
                ]
            )
            ->andReturn(
                $transformedSymfonyRequest
            );

        /** @var MockInterface|HttpMessageFactoryInterface $psrMessageFactory */
        $psrMessageFactory = Mockery::mock(HttpMessageFactoryInterface::class);
        $psrMessageFactory
            ->shouldReceive('createResponse')
            ->once()
            ->withArgs(
                [
                    $response
                ]
            )->andReturn(
                $expectedResponse
            );

        /** @var MockInterface|RequestStack $requestStack */
        $requestStack = Mockery::mock(RequestStack::class);
        $requestStack
            ->shouldReceive('pop')
            ->times(2)
            ->andReturn(
                $request,
                null
            );
        $requestStack
            ->shouldReceive('push')
            ->once()
            ->withArgs(
                [
                    $transformedSymfonyRequest
                ]
            );

        $originalController = function (Request $request, string $content) use ($response) {
            return $response;
        };

        $requestHandler = new SymfonyControllerRequestHandler(
            $foundationFactory,
            $psrMessageFactory,
            $requestStack,
            $originalController,
            [
                0 => $request,
                1 => $content
            ]
        );

        $psrResponse = $requestHandler->handle($psrRequest);
        $this->assertEquals($expectedResponse, $psrResponse);
    }
}