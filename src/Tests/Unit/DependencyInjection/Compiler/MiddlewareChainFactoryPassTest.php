<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\DependencyInjection\Compiler;

use DeepCopy\DeepCopy;
use Delvesoft\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Delvesoft\Symfony\Psr15Bundle\DependencyInjection\Compiler\MiddlewareChainFactoryPass;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathStrategyResolver;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameStrategyResolver;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MiddlewareChainFactoryPassTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanDetectConfigurationKey()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(false);

        $compilerPass->process($container);
        $this->assertTrue(true);
    }

    public function testCanHandleCachingDisabled()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(true);

        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(
                [
                    'use_cache'         => false,
                    'middleware_chains' => [],
                    'routing'           => []
                ]
            );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class
                ]
            )
            ->andReturn(
                $definition
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $compilerPass->process($container);
        $this->assertTrue(true);
    }

    public function testCanHandleCachingEnabled()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(true);

        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(
                [
                    'use_cache'         => true,
                    'middleware_chains' => [],
                    'routing'           => []
                ]
            );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $definition
            ->shouldReceive('setArguments')
            ->once()
            ->withArgs(
                function ($argument) {
                    if (!is_array($argument)) {
                        return false;
                    }

                    $currentArgument = current($argument);

                    return ($currentArgument instanceof Reference && (string)$currentArgument === 'MiddlewareChainResolverProxy');
                }
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class
                ]
            )
            ->andReturn(
                $definition
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $compilerPass->process($container);
        $this->assertTrue(true);
    }

    public function testCanHandleNonExistingMiddlewareDuringChainCreation()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(true);

        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(
                [
                    'use_cache'         => false,
                    'middleware_chains' => [
                        'Test' => [
                            '1',
                        ]
                    ],
                    'routing'           => []
                ]
            );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class
                ]
            )
            ->andReturn(
                $definition
            );

        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    '1'
                ]
            )
            ->andReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Middleware with service alias: [1] is not registered as a service');
        $compilerPass->process($container);
    }

    public function testCanHandleMiddlewareChainsCreation()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(true);

        $middlewareAliases = [
            '1',
            '2',
            '3'
        ];
        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(
                [
                    'use_cache'         => false,
                    'middleware_chains' => [
                        'Test' => $middlewareAliases
                    ],
                    'routing'           => []
                ]
            );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class
                ]
            )
            ->andReturn(
                $definition
            );
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $newDefinitionArray = [];
        foreach ($middlewareAliases as $alias) {
            $container
                ->shouldReceive('hasDefinition')
                ->once()
                ->withArgs(
                    [
                        $alias
                    ]
                )
                ->andReturn(true);

            /** @var MockInterface|Definition $definition */
            $definition = Mockery::mock(Definition::class);
            $container
                ->shouldReceive('getDefinition')
                ->once()
                ->withArgs(
                    [
                        $alias
                    ]
                )
                ->andReturn(
                    $definition
                );

            /** @var MockInterface|Definition $definition */
            $newDefinition = Mockery::mock(Definition::class);
            $newDefinition
                ->shouldReceive('setPublic')
                ->once()
                ->withArgs(
                    [
                        false
                    ]
                )->andReturn(
                    $newDefinition
                );

            $newDefinition
                ->shouldReceive('setShared')
                ->once()
                ->withArgs(
                    [
                        false
                    ]
                );

            $newDefinitionArray[] = $newDefinition;
            $deepCopy
                ->shouldReceive('copy')
                ->once()
                ->withArgs(
                    [
                        $definition
                    ]
                )
                ->andReturn(
                    $newDefinition
                );
        }

        $newDefinitionArray[0]
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $newDefinitionArray[1],
                    ]
                ]
            );

        $newDefinitionArray[0]
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $newDefinitionArray[2],
                    ]
                ]
            );

        $compilerPass->process($container);
        $this->assertTrue(true);
    }

    public function testCanDetectNonExistingMiddlewareChainDuringRoutingRulesCompilation()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(true);

        $middlewareAliases = [
            '1',
            '2',
            '3'
        ];
        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(
                [
                    'use_cache'         => false,
                    'middleware_chains' => [
                        'Test' => $middlewareAliases
                    ],
                    'routing'           => [
                        'Condition' => [
                            'middleware_chain' => 'ABCD'
                        ]
                    ]
                ]
            );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class
                ]
            )
            ->andReturn(
                $definition
            );
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $newDefinitionArray = [];
        foreach ($middlewareAliases as $alias) {
            $container
                ->shouldReceive('hasDefinition')
                ->once()
                ->withArgs(
                    [
                        $alias
                    ]
                )
                ->andReturn(true);

            /** @var MockInterface|Definition $definition */
            $definition = Mockery::mock(Definition::class);
            $container
                ->shouldReceive('getDefinition')
                ->once()
                ->withArgs(
                    [
                        $alias
                    ]
                )
                ->andReturn(
                    $definition
                );

            /** @var MockInterface|Definition $definition */
            $newDefinition = Mockery::mock(Definition::class);
            $newDefinition
                ->shouldReceive('setPublic')
                ->once()
                ->withArgs(
                    [
                        false
                    ]
                )->andReturn(
                    $newDefinition
                );

            $newDefinition
                ->shouldReceive('setShared')
                ->once()
                ->withArgs(
                    [
                        false
                    ]
                );

            $newDefinitionArray[] = $newDefinition;
            $deepCopy
                ->shouldReceive('copy')
                ->once()
                ->withArgs(
                    [
                        $definition
                    ]
                )
                ->andReturn(
                    $newDefinition
                );
        }

        $newDefinitionArray[0]
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $newDefinitionArray[1],
                    ]
                ]
            );

        $newDefinitionArray[0]
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $newDefinitionArray[2],
                    ]
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. Middleware chain with name: [ABCD] does not exist');
        $compilerPass->process($container);
    }

    public function testCanDetectEmptyConditionsOnRoutingConfig()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(true);

        $middlewareAliases = [
            '1',
            '2',
            '3'
        ];
        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'psr15'
                ]
            )
            ->andReturn(
                [
                    'use_cache'         => false,
                    'middleware_chains' => [
                        'Test' => $middlewareAliases
                    ],
                    'routing'           => [
                        'Condition' => [
                            'middleware_chain' => 'Test',
                            'conditions'       => [],
                        ]
                    ]
                ]
            );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class
                ]
            )
            ->andReturn(
                $definition
            );
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathStrategyResolver::class
                ]
            )
            ->andReturn(
                null
            );

        $newDefinitionArray = [];
        foreach ($middlewareAliases as $alias) {
            $container
                ->shouldReceive('hasDefinition')
                ->once()
                ->withArgs(
                    [
                        $alias
                    ]
                )
                ->andReturn(true);

            /** @var MockInterface|Definition $definition */
            $definition = Mockery::mock(Definition::class);
            $container
                ->shouldReceive('getDefinition')
                ->once()
                ->withArgs(
                    [
                        $alias
                    ]
                )
                ->andReturn(
                    $definition
                );

            /** @var MockInterface|Definition $definition */
            $newDefinition = Mockery::mock(Definition::class);
            $newDefinition
                ->shouldReceive('setPublic')
                ->once()
                ->withArgs(
                    [
                        false
                    ]
                )->andReturn(
                    $newDefinition
                );

            $newDefinition
                ->shouldReceive('setShared')
                ->once()
                ->withArgs(
                    [
                        false
                    ]
                );

            $newDefinitionArray[] = $newDefinition;
            $deepCopy
                ->shouldReceive('copy')
                ->once()
                ->withArgs(
                    [
                        $definition
                    ]
                )
                ->andReturn(
                    $newDefinition
                );
        }

        $newDefinitionArray[0]
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $newDefinitionArray[1],
                    ]
                ]
            );

        $newDefinitionArray[0]
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $newDefinitionArray[2],
                    ]
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. At least one condition has to be specified');
        $compilerPass->process($container);
    }
}