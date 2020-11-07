<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestMiddlewareResolverTest extends MockeryTestCase
{
    public function testCanResolveMiddlewareChain()
    {
        $routeName  = 'test';
        $httpMethod = 'POST';
        $request    = new Request(
            [],
            [],
            [
                '_route' => $routeName
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethod
            ]
        );


        $httpMethodValueObject = HttpMethod::createFromString($httpMethod);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractChainResolver $middlewareResolverChain */
        $middlewareResolverChain = Mockery::mock(AbstractChainResolver::class);
        $middlewareResolverChain
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                function ($argument) use ($routeName, $httpMethodValueObject) {
                    if (!($argument instanceof MiddlewareResolvingRequest)) {
                        return false;
                    }

                    return ($argument->getRouteName() === $routeName && $argument->getHttpMethod()->equals($httpMethodValueObject));
                }
            )->andReturn(
                $middleware
            );

        $resolver = new RequestMiddlewareResolver(
            $middlewareResolverChain
        );

        $resolvedMiddleware = $resolver->resolveMiddlewareChain($request);
        $this->assertEquals($middleware, $resolvedMiddleware);
    }

    public function testCanAddLocaleToRouteName()
    {
        $routeName  = 'test';
        $locale     = 'en';
        $httpMethod = 'POST';
        $request    = new Request(
            [],
            [],
            [
                '_route' => $routeName,
                '_locale' => $locale
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethod
            ]
        );


        $httpMethodValueObject = HttpMethod::createFromString($httpMethod);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractChainResolver $middlewareResolverChain */
        $middlewareResolverChain = Mockery::mock(AbstractChainResolver::class);
        $middlewareResolverChain
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                function ($argument) use ($routeName, $locale, $httpMethodValueObject) {
                    if (!($argument instanceof MiddlewareResolvingRequest)) {
                        return false;
                    }

                    return ($argument->getRouteName() === "{$routeName}.{$locale}" && $argument->getHttpMethod()->equals($httpMethodValueObject));
                }
            )->andReturn(
                $middleware
            );

        $resolver = new RequestMiddlewareResolver(
            $middlewareResolverChain
        );

        $resolvedMiddleware = $resolver->resolveMiddlewareChain($request);
        $this->assertEquals($middleware, $resolvedMiddleware);
    }
}