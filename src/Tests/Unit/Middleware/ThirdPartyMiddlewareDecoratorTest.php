<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Middleware;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\ThirdPartyMiddlewareDecorator;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ThirdPartyMiddlewareDecoratorTest extends MockeryTestCase
{
    public function testCanDelegateCallToDecoratedObject()
    {
        /** @var MockInterface|ServerRequestInterface $request */
        $request = Mockery::mock(ServerRequestInterface::class);

        /** @var MockINterface|RequestHandlerInterface $handler */
        $handler = Mockery::mock(RequestHandlerInterface::class);

        /** @var AbstractMiddlewareChainItem|MockInterface $objectToBeDecorated */
        $objectToBeDecorated = Mockery::mock(AbstractMiddlewareChainItem::class);
        $objectToBeDecorated
            ->shouldReceive('process')
            ->once()
            ->withArgs(
                [
                    $request,
                    $handler
                ]
            );

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $decorator = new ThirdPartyMiddlewareDecorator(
            $serverRequestFactory,
            $responseFactory,
            $objectToBeDecorated
        );

        $decorator->process($request, $handler);
    }
}
