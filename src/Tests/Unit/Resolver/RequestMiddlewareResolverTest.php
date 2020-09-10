<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\Resolver;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolver;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolverItem;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RequestMiddlewareResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

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

        /** @var MockInterface|AbstractChainResolverItem $middlewareResolverChain */
        $middlewareResolverChain = Mockery::mock(AbstractChainResolverItem::class);
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

        /** @var MockInterface|AbstractChainResolverItem $middlewareResolverChain */
        $middlewareResolverChain = Mockery::mock(AbstractChainResolverItem::class);
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