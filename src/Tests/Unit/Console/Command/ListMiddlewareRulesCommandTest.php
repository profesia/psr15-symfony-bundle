<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Console\Command;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Psr15\Middleware\Factory\MiddlewareChainFactory;
use Mockery;
use Mockery\MockInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Profesia\Symfony\Psr15Bundle\Console\Command\ListMiddlewareRulesCommand;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ListMiddlewareRulesCommandTest extends MockeryTestCase
{
    public function testCanListRouteNameMiddlewares()
    {
        $factory = new Psr17Factory();
        $chain   = MiddlewareChainFactory::createFromArray(
            [
                new Middleware1(
                    $factory,
                    $factory
                ),
                new Middleware2(
                    $factory,
                    $factory
                ),
                new Middleware3(
                    $factory,
                    $factory
                ),
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
        $middlewareCLasses = $chain->listChainClassNames();
        foreach ($middlewareCLasses as $class) {
            $this->assertStringContainsString($class, $display);
        }
    }

    public function testCanListPathNameMiddlewares()
    {
        $factory = new Psr17Factory();
        $chain   = MiddlewareChainFactory::createFromArray(
            [
                new Middleware1(
                    $factory,
                    $factory
                ),
                new Middleware3(
                    $factory,
                    $factory
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
        $middlewareCLasses = $chain->listChainClassNames();
        foreach ($middlewareCLasses as $class) {
            $this->assertStringContainsString($class, $display);
        }
    }
}

class Middleware1 extends AbstractMiddlewareChainItem
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->processNext(
            $request,
            $handler
        );
    }
}

class Middleware2 extends AbstractMiddlewareChainItem
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->processNext(
            $request,
            $handler
        );
    }
}

class Middleware3 extends AbstractMiddlewareChainItem
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->processNext(
            $request,
            $handler
        );
    }
}