<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Console\Command;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Console\Command\WarmUpMiddlewareCacheCommand;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverCachingInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class WarmUpMiddlewareCacheCommandTest extends MockeryTestCase
{
    public function httpMethodsDataProvider()
    {
        return [
            [['GET', 'POST'], ['GET', 'POST']],
            [[], HttpMethod::getPossibleValues()],
        ];
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

        /** @var MiddlewareResolverCachingInterface|MockInterface $resolver */
        $resolver = Mockery::mock(MiddlewareResolverCachingInterface::class);
        new WarmUpMiddlewareCacheCommand(
            $router,
            $resolver
        );
    }

    public function testConfiguration()
    {
        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('all')
            ->once()
            ->andReturn(
                []
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        /** @var MockInterface|MiddlewareResolverCachingInterface $resolverCacheProxy */
        $resolverCacheProxy = Mockery::mock(MiddlewareResolverCachingInterface::class);

        $command = new WarmUpMiddlewareCacheCommand(
            $router,
            $resolverCacheProxy
        );

        $this->assertEquals(
            'profesia:middleware:warm-up',
            $command->getName()
        );

        $this->assertEquals(
            'Warms up the middleware cache',
            $command->getDescription()
        );
    }

    /**
     * @dataProvider httpMethodsDataProvider
     *
     * @param array $inputHttpMethods
     * @param array $checkedHttpMethods
     */
    public function testCanExecuteWithSpecifiedHttpMethods(array $inputHttpMethods, array $checkedHttpMethods)
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

        $route1
            ->shouldReceive('getMethods')
            ->andReturn(
                $inputHttpMethods
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

        $index = 0;
        /** @var MiddlewareResolverCachingInterface|MockInterface $resolver */
        $resolver = Mockery::mock(MiddlewareResolverCachingInterface::class);
        $resolver
            ->shouldReceive('resolveMiddlewareChain')
            ->times(2)
            ->withArgs(
                function (MiddlewareResolvingRequest $argument) use ($checkedHttpMethods, &$index) {
                    if ($argument->hasAccessKey()) {
                        return false;
                    }

                    if ($argument->getAccessKey() !== null) {
                        return false;
                    }

                    if (!$argument->getHttpMethod()->equals(HttpMethod::createFromString($checkedHttpMethods[$index]))) {
                        return false;
                    }
                    $index++;

                    return true;
                }
            )->andReturn(
                ResolvedMiddlewareChain::createDefault()
            );

        $command = new WarmUpMiddlewareCacheCommand(
            $router,
            $resolver
        );

        $tester = new CommandTester(
            $command
        );
        $statusCode = $tester->execute([]);
        $this->assertEquals(1, $statusCode);
    }
}
