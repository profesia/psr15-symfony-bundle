<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\Middleware;

use Delvesoft\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Mockery;
use Mockery\MockInterface;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NullMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanHandle()
    {
        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);

        $headers  = [
            'Content-Type' => 'abcd'
        ];
        $body     = 'Testing body';
        $response = new Response(
            201,
            $headers,
            $body
        );

        /** @var MockINterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler
            ->shouldReceive(
                'handle'
            )
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )->andReturn($response);

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $middleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

        $realResponse = $middleware->process(
            $request,
            $handler
        );

        $this->assertEquals($response, $realResponse);
    }
}