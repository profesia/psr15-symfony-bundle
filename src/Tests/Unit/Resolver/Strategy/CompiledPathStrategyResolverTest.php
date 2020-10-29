<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathStrategyResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CompiledPathStrategyResolverTest extends MockeryTestCase
{
    public function testWillAppendNewRuleToExistingRuleBasedOnHttpMethod()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new CompiledPathStrategyResolver(
            $middlewareChainItemFactory,
            $router
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware1
            ->shouldReceive('append')
            ->once()
            ->withArgs(
                [
                    $middleware2
                ]
            )->andReturn(
                $middleware1
            );

        $configurationPath = ConfigurationPath::createFromConfigurationHttpMethodAndString(
            ConfigurationHttpMethod::createFromString('POST'),
            '/test'
        );

        $middlewares = [
            'POST' => $middleware1
        ];
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );

        $middlewares['POST'] = $middleware2;
        $resolver->registerPathMiddleware(
            $configurationPath,
            $middlewares
        );
    }

    public function testCanExport()
    {

    }
}