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

    private static function setUpStandardContainerExpectations(array $middlewareChain, ?array $routing): array
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

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

        $routingConfig = [];
        if ($routing !== null) {
            $routingConfig = [
                'Condition' => $routing
            ];
        }
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
                        'Test' => $middlewareChain
                    ],
                    'routing'           => $routingConfig
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

        /** @var MockInterface|RouteNameStrategyResolver $routeNameStrategyResolver */
        $routeNameStrategyResolver = Mockery::mock(RouteNameStrategyResolver::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameStrategyResolver::class
                ]
            )
            ->andReturn(
                $routeNameStrategyResolver
            );

        /** @var MockInterface|CompiledPathStrategyResolver $compiledPathStrategyResolver */
        $compiledPathStrategyResolver = Mockery::mock(CompiledPathStrategyResolver::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathStrategyResolver::class
                ]
            )
            ->andReturn(
                $compiledPathStrategyResolver
            );

        $newDefinitionArray = [];
        foreach ($middlewareChain as $alias) {
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
                )
                ->andReturn(
                    $newDefinition
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

        $firstDefinition = $newDefinitionArray[0];
        unset($newDefinitionArray[0]);
        foreach ($newDefinitionArray as $key => $definition) {
            $firstDefinition
                ->shouldReceive('addMethodCall')
                ->once()
                ->withArgs(
                    [
                        'append',
                        [
                            $definition,
                        ]
                    ]
                );
        }

        return [
            'container'                    => $container,
            'deepCopy'                     => $deepCopy,
            'middlewareChain'              => $firstDefinition,
            'routeNameStrategyResolver'    => $routeNameStrategyResolver,
            'compiledPathStrategyResolver' => $compiledPathStrategyResolver
        ];
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
            ->shouldReceive('replaceArgument')
            ->once()
            ->withArgs(
                function ($name, $argument) {
                    if ($name !== '$httpMiddlewareResolver') {
                        return false;
                    }

                    return ($argument instanceof Reference && (string)$argument === 'MiddlewareChainResolverProxy');
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
        ['container' => $container, 'deepCopy' => $deepCopy] = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            null
        );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $compilerPass->process($container);
        $this->assertTrue(true);
    }

    public function testCanDetectNonExistingMiddlewareChainDuringRoutingRulesCompilation()
    {
        ['container' => $container, 'deepCopy' => $deepCopy] = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'ABCD',
                'conditions'       => [],
            ]
        );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. Middleware chain with name: [ABCD] does not exist');
        $compilerPass->process($container);
    }

    public function testCanDetectEmptyConditionsOnRoutingConfig()
    {
        ['container' => $container, 'deepCopy' => $deepCopy] = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'conditions'       => [],
            ]
        );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. At least one condition has to be specified');
        $compilerPass->process($container);
    }

    public function testCanDetectInvalidConfigurationInRoutingConfig()
    {
        $mocks = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'conditions'       => [
                    [
                        'path'       => '/test',
                        'route_name' => 'test'
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|Definition $middlewareChain */
        $middlewareChain = $mocks['middlewareChain'];

        /** @var MockInterface|Definition $newMiddlewareChain */
        $newMiddlewareChain = Mockery::mock(Definition::class);
        $newMiddlewareChain
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )->andReturn(
                $newMiddlewareChain
            );

        $newMiddlewareChain
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false
                ]
            );

        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $middlewareChain
                ]
            )
            ->andReturn(
                $newMiddlewareChain
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Error in condition config: [Condition]. Condition config has to have either 'route_name' or 'path' config");
        $compilerPass->process($container);
    }

    public function testCanDetectInvalidConfigurationInRoutingRouteNameConfig()
    {
        $mocks = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'conditions'       => [
                    [
                        'route_name' => 'test',
                        'method'     => 'test'
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|Definition $middlewareChain */
        $middlewareChain = $mocks['middlewareChain'];

        /** @var MockInterface|Definition $newMiddlewareChain */
        $newMiddlewareChain = Mockery::mock(Definition::class);
        $newMiddlewareChain
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )->andReturn(
                $newMiddlewareChain
            );

        $newMiddlewareChain
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false
                ]
            );

        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $middlewareChain
                ]
            )
            ->andReturn(
                $newMiddlewareChain
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Error in condition config: [Condition]. Key: 'method' is redundant for condition with 'route_name'");
        $compilerPass->process($container);
    }

    public function testCanDetectNonExistingMiddlewareInPrependSection()
    {
        $mocks = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'prepend'          => [
                    'abcd'
                ],
                'conditions'       => [
                    [
                        'route_name' => 'test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    'abcd'
                ]
            )
            ->andReturn(
                false
            );

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. Middleware with service alias: [abcd] is not registered as a service');
        $compilerPass->process($container);
    }

    public function testCanDetectPrependingOfAOtherMiddlewareChain()
    {
        $mocks = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'prepend'          => [
                    '1'
                ],
                'conditions'       => [
                    [
                        'route_name' => 'test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    '1'
                ]
            )
            ->andReturn(
                true
            );


        /** @var MockInterface|Definition $originalDefinition */
        $originalDefinition = Mockery::mock(Definition::class);
        $originalDefinition
            ->shouldReceive('getMethodCalls')
            ->once()
            ->andReturn(
                [
                    'test'
                ]
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    '1'
                ]
            )
            ->andReturn(
                $originalDefinition
            );

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. Middleware to prepend must not be a middleware chain');
        $compilerPass->process($container);
    }

    public function testCanCreateMiddlewareChainInPrependSection()
    {
        $mocks = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'prepend'          => [
                    '4',
                    '5'
                ],
                'conditions'       => [
                    [
                        'route_name' => 'test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|Definition $middlewareChain */
        $middlewareChain = $mocks['middlewareChain'];

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    '4'
                ]
            )
            ->andReturn(
                true
            );
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    '5'
                ]
            )
            ->andReturn(
                true
            );


        /** @var MockInterface|Definition $prependDefinition1 */
        $prependDefinition1 = Mockery::mock(Definition::class);
        $prependDefinition1
            ->shouldReceive('getMethodCalls')
            ->once()
            ->andReturn(
                []
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    '4'
                ]
            )
            ->andReturn(
                $prependDefinition1
            );

        /** @var MockInterface|Definition $prependDefinition2 */
        $prependDefinition2 = Mockery::mock(Definition::class);
        $prependDefinition2
            ->shouldReceive('getMethodCalls')
            ->once()
            ->andReturn(
                []
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    '5'
                ]
            )
            ->andReturn(
                $prependDefinition2
            );

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|Definition $newPrependDefinition1 */
        $newPrependDefinition1 = Mockery::mock(Definition::class);
        $newPrependDefinition1
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )->andReturn(
                $newPrependDefinition1
            );

        $newPrependDefinition1
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )
            ->andReturn(
                $newPrependDefinition1
            );

        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $prependDefinition1
                ]
            )
            ->andReturn(
                $newPrependDefinition1
            );

        /** @var MockInterface|Definition $newPrependDefinition2 */
        $newPrependDefinition2 = Mockery::mock(Definition::class);
        $newPrependDefinition2
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )->andReturn(
                $newPrependDefinition2
            );

        $newPrependDefinition2
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )
            ->andReturn(
                $newPrependDefinition2
            );

        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $prependDefinition2
                ]
            )
            ->andReturn(
                $newPrependDefinition2
            );

        $newPrependDefinition1
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $newPrependDefinition2
                    ]
                ]
            )
            ->andReturn($newPrependDefinition1);

        $newPrependDefinition1
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'append',
                    [
                        $middlewareChain
                    ]
                ]
            )
            ->andReturn(
                $newPrependDefinition1
            );

        /** @var MockInterface|Definition $routeNameStrategyResolver */
        $routeNameStrategyResolver = $mocks['routeNameStrategyResolver'];
        $routeNameStrategyResolver
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                [
                    'registerRouteMiddleware',
                    [
                        'test',
                        $newPrependDefinition1
                    ]
                ]
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $compilerPass->process($container);
        $this->assertTrue(true);
    }

    public function testCanDetectNonExistingMiddlewareInAppendingSection()
    {
        $mocks = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'append'           => [
                    'abcd'
                ],
                'conditions'       => [
                    [
                        'route_name' => 'test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    'abcd'
                ]
            )
            ->andReturn(
                false
            );

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|Definition $middlewareChain */
        $middlewareChain = $mocks['middlewareChain'];

        /** @var MockInterface|Definition $newMiddlewareChain */
        $newMiddlewareChain = Mockery::mock(Definition::class);
        $newMiddlewareChain
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )->andReturn(
                $newMiddlewareChain
            );

        $newMiddlewareChain
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false
                ]
            );

        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $middlewareChain
                ]
            )
            ->andReturn(
                $newMiddlewareChain
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. Middleware with service alias: [abcd] is not registered as a service');
        $compilerPass->process($container);
    }

    public function testCanDetectAppendingOfOtherMiddlewareChain()
    {
        $mocks = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            [
                'middleware_chain' => 'Test',
                'append'           => [
                    '1'
                ],
                'conditions'       => [
                    [
                        'route_name' => 'test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];
        $container
            ->shouldReceive('hasDefinition')
            ->once()
            ->withArgs(
                [
                    '1'
                ]
            )
            ->andReturn(
                true
            );


        /** @var MockInterface|Definition $originalDefinition */
        $originalDefinition = Mockery::mock(Definition::class);
        $originalDefinition
            ->shouldReceive('getMethodCalls')
            ->once()
            ->andReturn(
                [
                    'test'
                ]
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    '1'
                ]
            )
            ->andReturn(
                $originalDefinition
            );

        /** @var MockInterface|Definition $middlewareChain */
        $middlewareChain = $mocks['middlewareChain'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|Definition $newMiddlewareChain */
        $newMiddlewareChain = Mockery::mock(Definition::class);
        $newMiddlewareChain
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false
                ]
            )->andReturn(
                $newMiddlewareChain
            );

        $newMiddlewareChain
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false
                ]
            );

        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $middlewareChain
                ]
            )
            ->andReturn(
                $newMiddlewareChain
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. Middleware to append must not be a middleware chain');
        $compilerPass->process($container);
    }
}