<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathStrategyResolver;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CompiledPathStrategyResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testWillIgnoreDuplicityRegistrationOfMiddlewareRule()
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

        $exportedRules = $resolver->exportRules();
        $this->assertEmpty($exportedRules);

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware1 */
        $middleware1 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware1
            ->shouldReceive('listChainClassNames')
            ->times(2)
            ->andReturn(
                [
                    'middleware1'
                ]
            );

        $configurationPath = ConfigurationPath::createFromConfigurationHttpMethodAndString(
            ConfigurationHttpMethod::createFromString('POST'),
            '/test'
        );

        $resolver->registerPathMiddleware(
            $configurationPath,
            $middleware1
        );
        
        $exportedRules = $resolver->exportRules();
        $this->assertCount(1, $exportedRules);

        /** @var ExportedMiddleware $rule */
        $rule = current($exportedRules);
        $this->assertEquals('POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('/test', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );

        /** @var MockInterface|AbstractMiddlewareChainItem $middleware2 */
        $middleware2 = Mockery::mock(AbstractMiddlewareChainItem::class);
        $middleware2
            ->shouldNotReceive('listChainClassNames');

        $resolver->registerPathMiddleware(
            $configurationPath,
            $middleware2
        );

        $exportedRules = $resolver->exportRules();
        $this->assertCount(1, $exportedRules);

        /** @var ExportedMiddleware $rule */
        $rule = current($exportedRules);
        $this->assertEquals('POST', $rule->getHttpMethods()->listMethods('|'));
        $this->assertEquals('/test', $rule->getIdentifier());
        $this->assertEquals(
            [
                0 => 'middleware1',
            ],
            $rule->listMiddlewareChainItems()
        );
    }
}