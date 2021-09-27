<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Console\Command;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Console\Command\ListMiddlewareRulesCommand;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware3;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Symfony\Component\Console\Tester\CommandTester;

class ListMiddlewareRulesCommandTest extends MockeryTestCase
{
    public function testConfiguration()
    {
        /** @var MockInterface|RouteNameResolver $routeNameStrategyResolver */
        $routeNameStrategyResolver = Mockery::mock(RouteNameResolver::class);

        /** @var MockInterface|CompiledPathResolver $compiledPathStrategyResolver */
        $compiledPathStrategyResolver = Mockery::mock(CompiledPathResolver::class);

        $command = new ListMiddlewareRulesCommand(
            $routeNameStrategyResolver,
            $compiledPathStrategyResolver
        );

        $this->assertEquals(
            'profesia:middleware:list-rules',
            $command->getName()
        );

        $this->assertEquals(
            'Lists all registered middleware chains routing rules',
            $command->getDescription()
        );
    }

    public function testCanListRouteNameMiddlewares()
    {
        $chain   = new MiddlewareCollection(
            [
                new TestMiddleware1(),
                new TestMiddleware2(),
                new TestMiddleware3(),
            ]
        );

        /** @var MockInterface|RouteNameResolver $routeNameStrategyResolver */
        $routeNameStrategyResolver = Mockery::mock(RouteNameResolver::class);
        $routeNameStrategyResolver
            ->shouldReceive('exportRules')
            ->once()
            ->andReturn(
                [
                    new ExportedMiddleware(
                        $chain,
                        CompoundHttpMethod::createFromStrings(['GET', 'POST', 'PUT']),
                        '/test1',
                        'test1'
                    )
                ]
            );

        /** @var MockInterface|CompiledPathResolver $compiledPathStrategyResolver */
        $compiledPathStrategyResolver = Mockery::mock(CompiledPathResolver::class);
        $compiledPathStrategyResolver
            ->shouldReceive('exportRules')
            ->once()
            ->andReturn(
                []
            );

        $command = new ListMiddlewareRulesCommand(
            $routeNameStrategyResolver,
            $compiledPathStrategyResolver
        );

        $tester = new CommandTester(
            $command
        );

        $statusCode = $tester->execute([]);
        $display    = $tester->getDisplay();

        $this->assertEquals(1, $statusCode);
        $this->assertStringContainsString('test1', $display);
        $this->assertStringContainsString('GET | POST | PUT', $display);
        $middlewareCLasses = $chain->listClassNames();
        foreach ($middlewareCLasses as $class) {
            $this->assertStringContainsString($class, $display);
        }
    }

    public function testCanListPathNameMiddlewares()
    {
        $chain   = new MiddlewareCollection(
            [
                new TestMiddleware1(
                ),
                new TestMiddleware3(
                ),
            ]
        );

        /** @var MockInterface|RouteNameResolver $routeNameStrategyResolver */
        $routeNameStrategyResolver = Mockery::mock(RouteNameResolver::class);
        $routeNameStrategyResolver
            ->shouldReceive('exportRules')
            ->once()
            ->andReturn(
                []
            );

        /** @var MockInterface|CompiledPathResolver $compiledPathStrategyResolver */
        $compiledPathStrategyResolver = Mockery::mock(CompiledPathResolver::class);
        $compiledPathStrategyResolver
            ->shouldReceive('exportRules')
            ->once()
            ->andReturn(
                [
                    new ExportedMiddleware(
                        $chain,
                        CompoundHttpMethod::createFromStrings(['POST', 'PUT', 'DELETE']),
                        '/test2',
                        'test2'
                    )
                ]
            );

        $command = new ListMiddlewareRulesCommand(
            $routeNameStrategyResolver,
            $compiledPathStrategyResolver
        );

        $tester = new CommandTester(
            $command
        );

        $statusCode = $tester->execute([]);
        $display    = $tester->getDisplay();

        $this->assertEquals(1, $statusCode);
        $this->assertStringContainsString('test2', $display);
        $this->assertStringContainsString('POST | PUT | DELETE', $display);
        $middlewareCLasses = $chain->listClassNames();
        foreach ($middlewareCLasses as $class) {
            $this->assertStringContainsString($class, $display);
        }
    }
}
