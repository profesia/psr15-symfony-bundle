<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

class MiddlewareResolverTest extends MockeryTestCase
{
    public function testCanResolveMiddlewareChain()
    {
        $routeName  = 'test';
        $httpMethod = 'POST';
        $request    = new Request(
            [],
            [],
            [
                '_route' => $routeName,
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethod,
            ]
        );

        $httpMethodValueObject = HttpMethod::createFromString($httpMethod);
        $middlewareNames       = [
            'Middleware1',
            'Middleware2',
        ];

        /** @var MockInterface|MiddlewareCollection $middlewareChain */
        $middlewareChain = Mockery::mock(MiddlewareCollection::class);
        $middlewareChain
            ->shouldReceive('listClassNames')
            ->once()
            ->andReturn(
                $middlewareNames
            );
        $middlewareChain
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddleware */
        $resolvedMiddleware = Mockery::mock(ResolvedMiddlewareChain::class);
        $resolvedMiddleware
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    1,
                    2,
                    3,
                ],
            ]
        );

        $resolvedMiddleware
            ->shouldReceive('getMiddlewareAccessKey')
            ->once()
            ->andReturn(
                $accessKey
            );
        $resolvedMiddleware
            ->shouldReceive('listChainClassNames')
            ->once()
            ->andReturn(
                $middlewareNames
            );

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
                $resolvedMiddleware
            );

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('log')
            ->once()
            ->withArgs(
                function (string $logLevel, string $errorMessage, array $context) use ($accessKey, $middlewareNames) {
                    if ($logLevel !== LogLevel::INFO) {
                        return false;
                    }

                    if ($errorMessage !== 'Resolved middleware chain.') {
                        return false;
                    }

                    if (['accessKey' => $accessKey->toArray(), 'middlewareChain' => $middlewareNames] !== $context) {
                        return false;
                    }

                    return true;
                }
            );

        $resolver = new MiddlewareResolver(
            $middlewareResolverChain,
            $logger
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/test'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );

        $returnedMiddleware = $resolver->resolveMiddlewareChain($middlewareResolvingRequest);
        $this->assertEquals($resolvedMiddleware, $returnedMiddleware);
        $this->assertFalse($returnedMiddleware->isNullMiddleware());
        $this->assertEquals($accessKey, $returnedMiddleware->getMiddlewareAccessKey());
    }

    public function testWillNotLogOnNullLogger()
    {
        $routeName  = 'test';
        $httpMethod = 'POST';
        $request    = new Request(
            [],
            [],
            [
                '_route' => $routeName,
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethod,
            ]
        );


        $httpMethodValueObject = HttpMethod::createFromString($httpMethod);
        $middlewareNames       = [
            'Middleware1',
            'Middleware2',
        ];

        /** @var MockInterface|MiddlewareCollection $middlewareChain */
        $middlewareChain = Mockery::mock(MiddlewareCollection::class);
        $middlewareChain
            ->shouldReceive('listClassNames')
            ->once()
            ->andReturn(
                $middlewareNames
            );
        $middlewareChain
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddleware */
        $resolvedMiddleware = Mockery::mock(ResolvedMiddlewareChain::class);
        $resolvedMiddleware
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );
        $resolvedMiddleware
            ->shouldReceive('listChainClassNames')
            ->once()
            ->andReturn(
                $middlewareNames
            );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    1,
                    2,
                    3,
                ],
            ]
        );

        $resolvedMiddleware
            ->shouldReceive('getMiddlewareAccessKey')
            ->once()
            ->andReturn(
                $accessKey
            );

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
                $resolvedMiddleware
            );

        $resolver = new MiddlewareResolver(
            $middlewareResolverChain
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/test'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );

        $returnedMiddleware = $resolver->resolveMiddlewareChain($middlewareResolvingRequest);
        $this->assertEquals($resolvedMiddleware, $returnedMiddleware);
        $this->assertFalse($returnedMiddleware->isNullMiddleware());
        $this->assertEquals($accessKey, $returnedMiddleware->getMiddlewareAccessKey());
    }

    public function testCanFetchMiddlewareViaAccessKey()
    {
        $routeName  = 'test';
        $httpMethod = 'POST';
        $request    = new Request(
            [],
            [],
            [
                '_route' => $routeName,
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethod,
            ]
        );

        $middlewareNames = [
            'Middleware1',
            'Middleware2',
        ];

        /** @var MockInterface|MiddlewareCollection $middlewareChain */
        $middlewareChain = Mockery::mock(MiddlewareCollection::class);
        $middlewareChain
            ->shouldReceive('listClassNames')
            ->once()
            ->andReturn(
                $middlewareNames
            );
        $middlewareChain
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    1,
                    2,
                    3,
                ],
            ]
        );

        /** @var MockInterface|AbstractChainResolver $middlewareResolverChain */
        $middlewareResolverChain = Mockery::mock(AbstractChainResolver::class);
        $middlewareResolverChain
            ->shouldReceive('getChain')
            ->once()
            ->withArgs(
                [
                    $accessKey,
                ]
            )->andReturn(
                $middlewareChain
            );

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('log')
            ->once()
            ->withArgs(
                function (string $logLevel, string $errorMessage, array $context) use ($accessKey, $middlewareNames) {
                    if ($logLevel !== LogLevel::INFO) {
                        return false;
                    }

                    if ($errorMessage !== 'Fetched middleware chain from cache.') {
                        return false;
                    }

                    if (['accessKey' => $accessKey->toArray(), 'middlewareChain' => $middlewareNames] !== $context) {
                        return false;
                    }

                    return true;
                }
            );

        $resolver = new MiddlewareResolver(
            $middlewareResolverChain,
            $logger
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/test'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );
        $middlewareResolvingRequest = $middlewareResolvingRequest->withResolvedMiddlewareAccessCode($accessKey);

        $returnedMiddleware = $resolver->resolveMiddlewareChain($middlewareResolvingRequest);
        //$this->assertEquals($middlewareChain, $returnedMiddleware->getMiddlewareChain());
        $this->assertFalse($returnedMiddleware->isNullMiddleware());
        $this->assertEquals($accessKey, $returnedMiddleware->getMiddlewareAccessKey());
    }

    public function testWillLogWarningOnException()
    {
        $routeName  = 'test';
        $httpMethod = 'POST';
        $request    = new Request(
            [],
            [],
            [
                '_route' => $routeName,
            ],
            [],
            [],
            [
                'REQUEST_METHOD' => $httpMethod,
            ]
        );

        $httpMethodValueObject = HttpMethod::createFromString($httpMethod);
        $middlewareNames       = [
            'Middleware1',
            'Middleware2',
        ];

        /** @var MockInterface|MiddlewareCollection $middlewareChain */
        $middlewareChain = Mockery::mock(MiddlewareCollection::class);
        $middlewareChain
            ->shouldReceive('listClassNames')
            ->once()
            ->andReturn(
                $middlewareNames
            );
        $middlewareChain
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );

        /** @var MockInterface|ResolvedMiddlewareChain $resolvedMiddleware */
        $resolvedMiddleware = Mockery::mock(ResolvedMiddlewareChain::class);
        $resolvedMiddleware
            ->shouldReceive('isNullMiddleware')
            ->once()
            ->andReturn(
                false
            );
        $resolvedMiddleware
            ->shouldReceive('listChainClassNames')
            ->once()
            ->andReturn(
                $middlewareNames
            );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    1,
                    2,
                    3,
                ],
            ]
        );

        $resolvedMiddleware
            ->shouldReceive('getMiddlewareAccessKey')
            ->once()
            ->andReturn(
                $accessKey
            );

        $causeOfException = 'Testing';
        $exception        = new ChainNotFoundException($causeOfException);

        /** @var MockInterface|AbstractChainResolver $middlewareResolverChain */
        $middlewareResolverChain = Mockery::mock(AbstractChainResolver::class);
        $middlewareResolverChain
            ->shouldReceive('getChain')
            ->once()
            ->withArgs(
                [
                    $accessKey,
                ]
            )->andThrow(
                $exception
            );
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
                $resolvedMiddleware
            );

        /** @var MockInterface|LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('log')
            ->once()
            ->withArgs(
                function (string $logLevel, string $errorMessage, array $context) use ($accessKey, $causeOfException) {
                    if ($logLevel !== LogLevel::WARNING) {
                        return false;
                    }

                    if ($errorMessage !== "Unable to fetch cached resolver. Cause: [{$causeOfException}]. ") {
                        return false;
                    }

                    if (['accessKey' => $accessKey->toArray()] !== $context) {
                        return false;
                    }

                    return true;
                }
            );
        $logger
            ->shouldReceive('log')
            ->once()
            ->withArgs(
                function (string $logLevel, string $errorMessage, array $context) use ($accessKey, $middlewareNames) {
                    if ($logLevel !== LogLevel::INFO) {
                        return false;
                    }

                    if ($errorMessage !== 'Resolved middleware chain.') {
                        return false;
                    }

                    if (['accessKey' => $accessKey->toArray(), 'middlewareChain' => $middlewareNames] !== $context) {
                        return false;
                    }

                    return true;
                }
            );

        $resolver = new MiddlewareResolver(
            $middlewareResolverChain,
            $logger
        );

        /** @var CompiledRoute|MockInterface $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn(
                '/test'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );
        $middlewareResolvingRequest = $middlewareResolvingRequest->withResolvedMiddlewareAccessCode($accessKey);

        $returnedMiddleware = $resolver->resolveMiddlewareChain($middlewareResolvingRequest);
        //$this->assertEquals($middlewareChain, $returnedMiddleware->getMiddlewareChain());
        $this->assertFalse($returnedMiddleware->isNullMiddleware());
        $this->assertEquals($accessKey, $returnedMiddleware->getMiddlewareAccessKey());
    }
}
