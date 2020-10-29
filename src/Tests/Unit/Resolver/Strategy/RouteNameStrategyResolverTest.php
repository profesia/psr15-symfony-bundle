<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameStrategyResolver;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use RuntimeException;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteNameStrategyResolverTest extends MockeryTestCase
{
    public function testCanRegisterChainToAnyRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware1
            ->shouldReceive('listChainClassNames')
            ->times(3)
            ->andReturn(
                [
                    'middleware1'
                ]
            );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware2
            ->shouldNotReceive('listChainClassNames');

        $staticPrefix = 'prefix';
        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->times(3)
            ->andReturn(
                $staticPrefix
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('getMethods')
            ->times(3)
            ->andReturn(
                [
                    'GET',
                    'POST'
                ]
            );
        $route
            ->shouldReceive('compile')
            ->times(3)
            ->andReturn(
                $compiledRoute
            );

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->times(3)
            ->withArgs(
                [
                    '*'
                ]
            )->andReturn(
                $route
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameStrategyResolver(
            $factory,
            $router
        );

        $this->assertEmpty($resolver->exportRules());
        $resolver->registerRouteMiddleware('*', $middleware1);

        $rules = $resolver->exportRules();
        $this->assertCount(1, $rules);

        /** @var ExportedMiddleware $rule */
        $rule = current($rules);
        $this->assertEquals('GET|POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('*', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );

        $resolver->registerRouteMiddleware('*', $middleware2);
        $rules = $resolver->exportRules();
        $this->assertCount(1, $rules);

        /** @var ExportedMiddleware $rule */
        $rule = current($rules);
        $this->assertEquals('GET|POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('*', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );

        $resolver->registerRouteMiddleware('test', $middleware2);

        $rules = $resolver->exportRules();
        $this->assertCount(1, $rules);

        /** @var ExportedMiddleware $rule */
        $rule = current($rules);
        $this->assertEquals('GET|POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('*', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );
    }

    public function testCanRegisterChainToAStandardRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware1
            ->shouldReceive('listChainClassNames')
            ->times(3)
            ->andReturn(
                [
                    'middleware1'
                ]
            );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware2
            ->shouldNotReceive('listChainClassNames');

        $staticPrefix = 'prefix';
        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->times(3)
            ->andReturn(
                $staticPrefix
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('getMethods')
            ->times(3)
            ->andReturn(
                [
                    'GET',
                    'POST'
                ]
            );
        $route
            ->shouldReceive('compile')
            ->times(3)
            ->andReturn(
                $compiledRoute
            );

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->times(4)
            ->withArgs(
                [
                    'test'
                ]
            )->andReturn(
                $route
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameStrategyResolver(
            $factory,
            $router
        );

        $this->assertEmpty($resolver->exportRules());
        $resolver->registerRouteMiddleware('test', $middleware1);

        $rules = $resolver->exportRules();
        $this->assertCount(1, $rules);

        /** @var ExportedMiddleware $rule */
        $rule = current($rules);
        $this->assertEquals('GET|POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('test', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );

        $resolver->registerRouteMiddleware('test', $middleware2);

        $rules = $resolver->exportRules();
        $this->assertCount(1, $rules);

        /** @var ExportedMiddleware $rule */
        $rule = current($rules);
        $this->assertEquals('GET|POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('test', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );

        $resolver->registerRouteMiddleware('*', $middleware2);

        $rules = $resolver->exportRules();
        $this->assertCount(1, $rules);

        /** @var ExportedMiddleware $rule */
        $rule = current($rules);
        $this->assertEquals('GET|POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('test', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );
    }

    public function testWillDetectNonExistingRouteOnRegistration()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware2
            ->shouldNotReceive('listChainClassNames');


        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    'test'
                ]
            )->andReturn(
                null
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameStrategyResolver(
            $factory,
            $router
        );

        $this->assertEmpty($resolver->exportRules());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route with name: [test] is not registered");
        $resolver->registerRouteMiddleware('test', $middleware1);
        $this->assertEmpty($resolver->exportRules());
    }

    public function testCanResolveStandardRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);


        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);

        $routeName = 'test';

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->once()
            ->withArgs(
                [
                    $routeName
                ]
            )->andReturn(
                $route
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameStrategyResolver(
            $factory,
            $router
        );

        /** @var MockInterface|MiddlewareResolvingRequest $request */
        $request = Mockery::mock(MiddlewareResolvingRequest::class);
        $request
            ->shouldReceive('getRouteName')
            ->once()
            ->andReturn(
                $routeName
            );

        $resolver->registerRouteMiddleware($routeName, $middleware1);
        $resolvedMiddleware = $resolver->handle(
            $request
        );

        $this->assertEquals($middleware1, $resolvedMiddleware);
    }

    public function testCanResolveMagicRoute()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);


        $routeName = '*';

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

        $resolver = new RouteNameStrategyResolver(
            $factory,
            $router
        );

        /** @var MockInterface|MiddlewareResolvingRequest $request */
        $request = Mockery::mock(MiddlewareResolvingRequest::class);

        $resolver->registerRouteMiddleware($routeName, $middleware1);
        $resolvedMiddleware = $resolver->handle(
            $request
        );

        $this->assertEquals($middleware1, $resolvedMiddleware);
    }

    public function testCanHandleResolvingToNextResolver()
    {
        /** @var MockInterface|NullMiddleware $middleware1 */
        $middleware = Mockery::mock(NullMiddleware::class);

        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);
        $factory
            ->shouldReceive('createNullChainItem')
            ->once()
            ->andReturn(
                $middleware
            );


        $routeName = 'testing';

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

        $resolver = new RouteNameStrategyResolver(
            $factory,
            $router
        );

        /** @var MockInterface|MiddlewareResolvingRequest $request */
        $request = Mockery::mock(MiddlewareResolvingRequest::class);
        $request
            ->shouldReceive('getRouteName')
            ->once()
            ->andReturn(
                $routeName
            );

        $resolvedMiddleware = $resolver->handle(
            $request
        );

        $this->assertEquals($middleware, $resolvedMiddleware);
    }

    public function testWillDetectNonExistingRouteDuringExport()
    {
        /** @var MockInterface|MiddlewareChainItemFactory $factory */
        $factory = Mockery::mock(MiddlewareChainItemFactory::class);

        /** @var MockInterface|NullMiddleware $middleware1 */
        $middleware = Mockery::mock(NullMiddleware::class);

        $routeName = 'testing';

        /** @var MockInterface|RouteCollection $routeCollection */
        $routeCollection = Mockery::mock(RouteCollection::class);
        $routeCollection
            ->shouldReceive('get')
            ->times(1)
            ->withArgs(
                [
                    $routeName
                ]
            )
            ->andReturn(
                true
            );
        $routeCollection
            ->shouldReceive('get')
            ->times(1)
            ->withArgs(
                [
                    $routeName
                ]
            )
            ->andReturn(
                null
            );

        /** @var MockInterface|RouterInterface $router */
        $router = Mockery::mock(RouterInterface::class);
        $router
            ->shouldReceive('getRouteCollection')
            ->once()
            ->andReturn(
                $routeCollection
            );

        $resolver = new RouteNameStrategyResolver(
            $factory,
            $router
        );

        $resolver->registerRouteMiddleware($routeName, $middleware);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Route: [{$routeName}] is not registered");
        $resolver->exportRules();
    }
}