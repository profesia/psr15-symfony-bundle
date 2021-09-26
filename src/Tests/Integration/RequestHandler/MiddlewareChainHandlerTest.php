<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\RequestHandler;

use RuntimeException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Profesia\Symfony\Psr15Bundle\RequestHandler\MiddlewareChainHandler;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware3;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestRequestHandler;

class MiddlewareChainHandlerTest extends TestCase
{
    public function testCanCreateAndHandleServerRequest()
    {
        $m1 = new TestMiddleware1();
        $m2 = new TestMiddleware2();
        $m3 = new TestMiddleware3();


        $middlewares = [
            $m1,
            $m2,
            $m3,
            $m1,
            $m1,
            $m2,
        ];

        $chain = MiddlewareChainHandler::createFromFinalHandlerAndMiddlewares(
            new TestRequestHandler(),
            $middlewares
        );

        $response = $chain->handle(
            new ServerRequest(
                'GET',
                '',
                [],
                null
            )
        );

        $responseStream = $response->getBody();
        $responseStream->rewind();

        $this->assertEquals('1,2,3,1,1,2', $responseStream->getContents());
    }

    public function testWillThrowAnExceptionOnEmptyMiddlewaresInput()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('It is redundant to create MiddlewareChainHandler from empty array of middlewares');
        MiddlewareChainHandler::createFromFinalHandlerAndMiddlewares(
            new TestRequestHandler(),
            []
        );
    }
}
