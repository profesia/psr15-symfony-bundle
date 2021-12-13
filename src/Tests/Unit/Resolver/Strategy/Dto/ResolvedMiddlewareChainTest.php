<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy\Dto;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\RequestHandler\MiddlewareChainHandler;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResolvedMiddlewareChainTest extends MockeryTestCase
{
    public function testCanProcess()
    {
        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);

        /** @var MockInterface|ResponseInterface $response */
        $response = Mockery::mock(ResponseInterface::class);

        /** @var RequestHandlerInterface|MockInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        /** @var MockInterface|MiddlewareChainHandler $chainHandler */
        $chainHandler = Mockery::mock(MiddlewareChainHandler::class);
        $chainHandler
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )->andReturn(
                $response
            );

        /** @var MiddlewareCollection|MockInterface $middlewareCollection */
        $middlewareCollection = Mockery::mock(MiddlewareCollection::class);
        $middlewareCollection
            ->shouldReceive('transformToMiddlewareChainHandler')
            ->once()
            ->withArgs(
                [
                    $handler
                ]
            )->andReturn(
                $chainHandler
            );

        $resolvedMiddlewareChain = ResolvedMiddlewareChain::createFromResolverContext(
            $middlewareCollection,
            ResolvedMiddlewareAccessKey::createFromArray(
                [
                    'accessPath' => [],
                    'resolverClass' => RouteNameResolver::class
                ]
            )
        );

        $resolvedMiddlewareChain->process(
            $request,
            $handler
        );
    }
}
