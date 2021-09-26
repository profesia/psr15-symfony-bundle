<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\DependencyInjection\Compiler;

use RuntimeException;
use Mockery\MockInterface;
use Mockery;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler\MiddlewareChainResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MiddlewareChainResolverTest extends MockeryTestCase
{
    public function testCanResolveMiddlewareChains()
    {
        $classes          = [
            'Class1',
            'Class2',
            'Class3',
        ];
        $definitionConfig = [
            'Group1' => $classes,
        ];
        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);

        foreach ($classes as $class) {
            $container
                ->shouldReceive('hasDefinition')
                ->times(1)
                ->withArgs(
                    [
                        $class,
                    ]
                )
                ->andReturn(
                    true
                );

            /** @var MockInterface|Definition $definition */
            $definition = Mockery::mock(Definition::class);
            $definition
                ->shouldReceive('getMethodCalls')
                ->once()
                ->andReturn(
                    []
                );

            $container
                ->shouldReceive('getDefinition')
                ->times(1)
                ->withArgs(
                    [
                        $class,
                    ]
                )
                ->andReturn(
                    $definition
                );
        }


        $resolver    = new MiddlewareChainResolver(
            $container
        );
        $middlewares = $resolver->resolve(
            $definitionConfig
        );


        $this->assertTrue(count($middlewares) === 1);
        $this->assertTrue(count($middlewares['Group1']) === 3);
    }

    public function testCanIdentifyNonExistingMiddleware()
    {
        $classes          = [
            'Class1',
            'Class2',
            'Class3',
        ];
        $definitionConfig = [
            'Group1' => $classes,
        ];
        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);

        foreach ($classes as $class) {
            $isLastClass = ($class === 'Class3');
            $container
                ->shouldReceive('hasDefinition')
                ->times(1)
                ->withArgs(
                    [
                        $class,
                    ]
                )
                ->andReturn(
                    ($isLastClass === false)
                );

            if ($isLastClass === false) {
                /** @var MockInterface|Definition $definition */
                $definition = Mockery::mock(Definition::class);
                $definition
                    ->shouldReceive('getMethodCalls')
                    ->once()
                    ->andReturn(
                        []
                    );

                $container
                    ->shouldReceive('getDefinition')
                    ->times(1)
                    ->withArgs(
                        [
                            $class,
                        ]
                    )
                    ->andReturn(
                        $definition
                    );
            }
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Middleware with service alias: [Class3] is not registered as a service");
        $resolver    = new MiddlewareChainResolver(
            $container
        );
        $resolver->resolve(
            $definitionConfig
        );
    }

    public function testCanIdentifyNonSimpleServiceDefinition()
    {
        $classes          = [
            'Class1',
            'Class2',
            'Class3',
        ];
        $definitionConfig = [
            'Group1' => $classes,
        ];
        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);

        foreach ($classes as $class) {
            $isLastClass = ($class === 'Class3');
            $container
                ->shouldReceive('hasDefinition')
                ->times(1)
                ->withArgs(
                    [
                        $class,
                    ]
                )
                ->andReturn(
                    true
                );

            /** @var MockInterface|Definition $definition */
            $definition = Mockery::mock(Definition::class);
            $definition
                ->shouldReceive('getMethodCalls')
                ->once()
                ->andReturn(
                    ($isLastClass === false)
                        ? []
                        : ['test']
                );

            $container
                ->shouldReceive('getDefinition')
                ->times(1)
                ->withArgs(
                    [
                        $class,
                    ]
                )
                ->andReturn(
                    $definition
                );
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Middleware with service alias: [Class3] could not be included in chain. Only simple services (without additional calls) could be included"
        );
        $resolver    = new MiddlewareChainResolver(
            $container
        );
        $resolver->resolve(
            $definitionConfig
        );
    }
}
