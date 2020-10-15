<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\Console\Command;

use Delvesoft\Symfony\Psr15Bundle\Console\Command\WarmUpMiddlewareCacheCommand;
use Delvesoft\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverCachingInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class WarmUpMiddlewareCacheCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanCreate()
    {
        $routeCollection = new RouteCollection();

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router->shouldReceive('getRouteCollection')
               ->once()
               ->andReturn(
                   $routeCollection
               );

        /** @var RequestMiddlewareResolverCachingInterface|MockInterface $resolver */
        $resolver = Mockery::mock(RequestMiddlewareResolverCachingInterface::class);
        new WarmUpMiddlewareCacheCommand(
            $router,
            $resolver
        );

        $this->assertTrue(true);
    }

    public function testCanExecute()
    {
        $routeCollection = new RouteCollection();

        /** @var Route|MockInterface $route1 */
        $route1 = Mockery::mock(Route::class);
        $route1->shouldReceive('getPath')
               ->once()
               ->andReturn('/1');

        /** @var CompiledRoute|MockInterface $compiledRoute1 */
        $compiledRoute1 = Mockery::mock(CompiledRoute::class);
        $compiledRoute1
            ->shouldReceive('getStaticPrefix')
            ->once()
            ->andReturn('/1');

        $route1
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute1
            );

        $httpMethods = ['GET', 'POST'];
        $route1
            ->shouldReceive('getMethods')
            ->andReturn(
                $httpMethods
            );

        /** @var Route|MockInterface $route2 */
        $route2 = Mockery::mock(Route::class);
        $route2->shouldReceive('getPath')
               ->once()
               ->andReturn('/_2');

        $routeCollection->add(
            '1',
            $route1
        );

        $routeCollection->add(
            '2',
            $route2
        );

        /** @var RouterInterface|MockInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router->shouldReceive('getRouteCollection')
               ->once()
               ->andReturn(
                   $routeCollection
               );

        /** @var ServerRequestFactoryInterface|MockInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var ResponseFactoryInterface|MockInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $index = 0;
        /** @var RequestMiddlewareResolverCachingInterface|MockInterface $resolver */
        $resolver = Mockery::mock(RequestMiddlewareResolverCachingInterface::class);
        $resolver
            ->shouldReceive('resolveMiddlewareChain')
            ->times(2)
            ->withArgs(
                function (Request $argument) use ($httpMethods, &$index) {
                    $attributes = $argument->attributes;
                    if ($attributes->has('_route') === false) {
                        return false;
                    }

                    if ($attributes->get('_route') != '1') {
                        return false;
                    }

                    if ($argument->getMethod() !== $httpMethods[$index]) {
                        return false;
                    }
                    $index++;

                    return true;
                }
            )->andReturn(
                new NullMiddleware(
                    $serverRequestFactory,
                    $responseFactory
                )
            );

        $command = new WarmUpMiddlewareCacheCommand(
            $router,
            $resolver
        );

        $tester     = new CommandTester(
            $command
        );
        $statusCode = $tester->execute([]);
        $this->assertEquals(1, $statusCode);
    }
}