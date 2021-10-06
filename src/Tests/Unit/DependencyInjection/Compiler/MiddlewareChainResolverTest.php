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
    public static function getDefinitionConfig(): array
    {
        $classes = [
            'Class1',
            'Class2',
            'Class3',
        ];

        return [
            [
                [
                    'Group1' => $classes,
                ],
            ],
        ];
    }

    /**
     * @dataProvider getDefinitionConfig
     */
    public function testCanResolveMiddlewareChains(array $definitionConfig)
    {
        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $classes   = $definitionConfig['Group1'];

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


        $resolver = new MiddlewareChainResolver(
            $container
        );

        $middlewares = $resolver->resolve(
            $definitionConfig
        );


        $this->assertTrue(count($middlewares) === 1);
        $definition = $middlewares['Group1'];
        $this->assertCount(count($classes), $definition->getArguments()[0]);
    }

    /**
     * @dataProvider getDefinitionConfig
     */
    public function testCanIdentifyNonExistingMiddleware(array $definitionConfig)
    {
        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $classes   = $definitionConfig['Group1'];

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
        $resolver = new MiddlewareChainResolver(
            $container
        );
        $resolver->resolve(
            $definitionConfig
        );
    }

    /**
     * @dataProvider getDefinitionConfig
     */
    public function testCanIdentifyNonSimpleServiceDefinition(array $definitionConfig)
    {
        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $classes   = $definitionConfig['Group1'];

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
        $resolver = new MiddlewareChainResolver(
            $container
        );
        $resolver->resolve(
            $definitionConfig
        );
    }

    /**
     * @dataProvider getDefinitionConfig
     */
    public function testCanIdentifyNonExistingServiceDuringPrepend(array $definitionConfig)
    {
        $classes = $definitionConfig['Group1'];

        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    $classes[0],
                ]
            )
            ->andReturn(
                false
            );

        $resolver = new MiddlewareChainResolver(
            $container
        );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);

        $conditionName   = 'Test';
        $middlewareAlias = $classes[0];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
        );
        $resolver->resolveMiddlewaresToPrepend(
            $definition,
            [
                $classes[0],
                $classes[2],
            ],
            'Test'
        );
    }

    /**
     * @dataProvider getDefinitionConfig
     */
    public function testCanIdentifyNonExistingServiceDuringAppend(array $definitionConfig)
    {
        $classes = $definitionConfig['Group1'];

        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    $classes[0],
                ]
            )
            ->andReturn(
                false
            );

        $resolver = new MiddlewareChainResolver(
            $container
        );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);

        $conditionName   = 'Test';
        $middlewareAlias = $classes[0];
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
        );
        $resolver->resolveMiddlewaresToAppend(
            $definition,
            [
                $classes[0],
                $classes[2],
            ],
            'Test'
        );
    }

    /**
     * @dataProvider getDefinitionConfig
     */
    public function testCanPrependMiddlewares(array $definitionConfig)
    {
        $classes          = $definitionConfig['Group1'];
        $classesToPrepend = [
            $classes[0],
            $classes[2],
        ];

        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);

        $definitionsToPrepend = [
            0 => Mockery::mock(Definition::class),
            1 => Mockery::mock(Definition::class),
        ];

        $index = 0;
        foreach ($classesToPrepend as $classToPrepend) {
            $container
                ->shouldReceive('hasDefinition')
                ->once()
                ->withArgs(
                    [
                        $classToPrepend,
                    ]
                )
                ->andReturn(
                    true
                );

            $container
                ->shouldReceive('getDefinition')
                ->once()
                ->withArgs(
                    [
                        $classToPrepend,
                    ]
                )
                ->andReturn(
                    $definitionsToPrepend[$index++]
                );
        }

        $resolver = new MiddlewareChainResolver(
            $container
        );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);

        foreach (array_reverse($definitionsToPrepend) as $oneDefinitionToPrepend) {
            $definition
                ->shouldReceive('addMethodCall')
                ->once()
                ->withArgs(
                    [
                        'prepend',
                        [
                            $oneDefinitionToPrepend,
                        ],
                    ]
                );
        }

        $this->assertEquals(
            $definition,
            $resolver->resolveMiddlewaresToPrepend(
                $definition,
                $classesToPrepend,
                'Test'
            )
        );
    }

    /**
     * @dataProvider getDefinitionConfig
     */
    public function testCanAppendMiddlewares(array $definitionConfig)
    {
        $classes          = $definitionConfig['Group1'];
        $classesToAppend = [
            $classes[0],
            $classes[2],
        ];

        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);

        $definitionsToAppend = [
            0 => Mockery::mock(Definition::class),
            1 => Mockery::mock(Definition::class),
        ];

        $index = 0;
        foreach ($classesToAppend as $oneClassToAppend) {
            $container
                ->shouldReceive('hasDefinition')
                ->once()
                ->withArgs(
                    [
                        $oneClassToAppend,
                    ]
                )
                ->andReturn(
                    true
                );

            $container
                ->shouldReceive('getDefinition')
                ->once()
                ->withArgs(
                    [
                        $oneClassToAppend,
                    ]
                )
                ->andReturn(
                    $definitionsToAppend[$index++]
                );
        }

        $resolver = new MiddlewareChainResolver(
            $container
        );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);

        foreach ($definitionsToAppend as $oneDefinitionToAppend) {
            $definition
                ->shouldReceive('addMethodCall')
                ->once()
                ->withArgs(
                    [
                        'append',
                        [
                            $oneDefinitionToAppend,
                        ],
                    ]
                );
        }

        $this->assertEquals(
            $definition,
            $resolver->resolveMiddlewaresToAppend(
                $definition,
                $classesToAppend,
                'Test'
            )
        );
    }
}
