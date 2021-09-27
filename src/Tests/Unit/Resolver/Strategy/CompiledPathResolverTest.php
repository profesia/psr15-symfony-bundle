<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class CompiledPathResolverTest extends MockeryTestCase
{
    public function testWillAppendNewRuleToExistingRuleBasedOnHttpMethod()
    {
        $resolver = new CompiledPathResolver();

        $configurationPath = ConfigurationPath::createFromConfigurationHttpMethodAndString(
            ConfigurationHttpMethod::createFromString('POST'),
            '/test'
        );

        /** @var MockInterface|MiddlewareCollection $middlewareCollection2 */
        $middlewareCollection2 = Mockery::mock(MiddlewareCollection::class);

        /** @var MockInterface|MiddlewareCollection $middlewareCollection1 */
        $middlewareCollection1 = Mockery::mock(MiddlewareCollection::class);
        $middlewareCollection1
            ->shouldReceive('appendCollection')
            ->once()
            ->withArgs(
                [
                    $middlewareCollection2
                ]
            )->andReturn(
                $middlewareCollection1
            );

        $middlewares = [
            'POST' => $middlewareCollection1
        ];
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );

        $middlewares['POST'] = $middlewareCollection2;
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );

        $middlewares['GET'] = $middlewareCollection2;
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );
    }

    public function testWillHandleExecutionToNextHandlerOnNoRuleMatch()
    {
        $resolver = new CompiledPathResolver();

        $request                    = new Request();
        $route                      = new Route('/test');
        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            'test'
        );

        /** @var ResolvedMiddlewareChain|MockInterface $expectedMiddlewareChain */
        $expectedMiddlewareChain = Mockery::mock(ResolvedMiddlewareChain::class);

        /** @var MockInterface|AbstractChainResolver $handler */
        $handler = Mockery::mock(AbstractChainResolver::class);
        $handler
            ->shouldReceive(
                'handle'
            )
            ->once()
            ->withArgs(
                [
                    $middlewareResolvingRequest
                ]
            )
            ->andReturn(
                $expectedMiddlewareChain
            );

        $resolver->setNext(
            $handler
        );

        $resolvedMiddlewareChain = $resolver->handle(
            $middlewareResolvingRequest
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testCanDelegateGettingOfTheChainToNextHandler()
    {
        $resolver = new CompiledPathResolver();

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath' => [
                    '1',
                    '2'
                ],
            ]
        );

        /** @var MockInterface|MiddlewareCollection $expectedMiddlewareChain */
        $expectedMiddlewareChain = Mockery::mock(MiddlewareCollection::class);

        /** @var MockInterface|AbstractChainResolver $handler */
        $handler = Mockery::mock(AbstractChainResolver::class);
        $handler
            ->shouldReceive(
                'getChain'
            )
            ->once()
            ->withArgs(
                [
                    $accessKey
                ]
            )
            ->andReturn(
                $expectedMiddlewareChain
            );

        $resolver->setNext(
            $handler
        );

        $resolvedMiddlewareChain = $resolver->getChain(
            $accessKey
        );

        $this->assertEquals($expectedMiddlewareChain, $resolvedMiddlewareChain);
    }

    public function testWillThrowExceptionOnGettingMiddlewareChainWhenThereIsNoNextResolver()
    {
        $resolver = new CompiledPathResolver();

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => RouteNameResolver::class,
                'accessPath' => [
                    '1',
                    '2'
                ],
            ]
        );

        $this->expectException(ChainNotFoundException::class);
        $this->expectExceptionMessage('No resolver was able to retrieve middleware chain');
        $resolver->getChain(
            $accessKey
        );
    }

    public function testWillReturnNullMiddlewareOnNoNextHandlerRegistered()
    {
        /** @var MockInterface|NullMiddleware $nullMiddleware */
        $nullMiddleware = Mockery::mock(NullMiddleware::class);

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);
        $middlewareChainItemFactory
            ->shouldReceive('createNullChainItem')
            ->once()
            ->andReturn(
                $nullMiddleware
            );

        $resolver = new CompiledPathResolver();

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            new Request(),
            new Route('/test'),
            'test'
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        //$this->assertTrue($resolvedMiddlewareChain->getMiddlewareChain() === $nullMiddleware);
        $this->assertNull($resolvedMiddlewareChain->getMiddlewareAccessKey());
    }
}
