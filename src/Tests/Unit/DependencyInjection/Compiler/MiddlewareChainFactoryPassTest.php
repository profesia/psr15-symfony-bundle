<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\DependencyInjection\Compiler;

use DeepCopy\DeepCopy;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler\MiddlewareChainFactoryPass;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler\MiddlewareChainResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MiddlewareChainFactoryPassTest extends MockeryTestCase
{
    private static function setUpStandardContainerExpectations(array $middlewareChain, ?array $routing): array
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = Mockery::mock(MiddlewareChainResolver::class);

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(true);

        $routingConfig = [];
        if ($routing !== null) {
            $routingConfig = [
                'Condition' => $routing,
            ];
        }

        $config = [
            'use_cache'         => false,
            'middleware_chains' => [
                'Test' => $middlewareChain,
            ],
            'routing'           => $routingConfig,
        ];
        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(
                $config
            );

        /** @var MockInterface|Definition $adapterDefinition */
        $adapterDefinition = Mockery::mock(Definition::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class,
                ]
            )
            ->andReturn(
                $adapterDefinition
            );

        /** @var MockInterface|RouteNameResolver $routeNameStrategyResolver */
        $routeNameStrategyResolver = Mockery::mock(RouteNameResolver::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameResolver::class,
                ]
            )
            ->andReturn(
                $routeNameStrategyResolver
            );

        /** @var MockInterface|CompiledPathResolver $compiledPathStrategyResolver */
        $compiledPathStrategyResolver = Mockery::mock(CompiledPathResolver::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathResolver::class,
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
                        $alias,
                    ]
                )
                ->andReturn(true);

            /** @var MockInterface|Definition $middlewareDefinition */
            $middlewareDefinition = Mockery::mock(Definition::class);
            $middlewareDefinition
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
                        $alias,
                    ]
                )
                ->andReturn(
                    $middlewareDefinition
                );

            /** @var MockInterface|Definition $newDefinition */
            $newDefinition = Mockery::mock(Definition::class);
            $newDefinition
                ->shouldReceive('setPublic')
                ->once()
                ->withArgs(
                    [
                        false,
                    ]
                )->andReturn(
                    $newDefinition
                );

            $newDefinition
                ->shouldReceive('setPrivate')
                ->once()
                ->withArgs(
                    [
                        true,
                    ]
                )->andReturn(
                    $newDefinition
                );

            $newDefinition
                ->shouldReceive('setShared')
                ->once()
                ->withArgs(
                    [
                        false,
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
                        $middlewareDefinition,
                    ]
                )
                ->andReturn(
                    $newDefinition
                );
        }

        $firstDefinition = $newDefinitionArray[0];
        unset($newDefinitionArray[0]);
        foreach ($newDefinitionArray as $key => $adapterDefinition) {
            $firstDefinition
                ->shouldReceive('addMethodCall')
                ->once()
                ->withArgs(
                    [
                        'append',
                        [
                            $adapterDefinition,
                        ],
                    ]
                );
        }

        return [
            'config'                       => $config,
            'container'                    => $container,
            'deepCopy'                     => $deepCopy,
            'chainResolver'                => $chainResolver,
            'middlewareChain'              => $firstDefinition,
            'routeNameStrategyResolver'    => $routeNameStrategyResolver,
            'compiledPathStrategyResolver' => $compiledPathStrategyResolver,
        ];
    }

    public function testCanDetectConfigurationKey()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = Mockery::mock(MiddlewareChainResolver::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(false);

        $compilerPass->process($container);
    }

    public function provideDataForCachingTest(): array
    {
        return [
            [
                [
                    'use_cache'         => false,
                    'middleware_chains' => [],
                    'routing'           => [],
                ],
            ],
            [
                [
                    'use_cache'         => true,
                    'middleware_chains' => [],
                    'routing'           => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideDataForCachingTest
     *
     * @param array $config
     */
    public function testCanHandleCachingConfig(array $config)
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = Mockery::mock(MiddlewareChainResolver::class);

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(true);

        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(
                $config
            );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);

        if ($config['use_cache'] === true) {
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

                        return ($argument instanceof Reference && (string)$argument === 'MiddlewareChainResolverCaching');
                    }
                );

            $container
                ->shouldReceive('getDefinition')
                ->once()
                ->withArgs(
                    [
                        SymfonyControllerAdapter::class,
                    ]
                )
                ->andReturn(
                    $definition
                );
        }

        $compilerPass->turnOnCaching($definition, $config['use_cache']);
    }

    public function testCanHandleNonExistingMiddlewareDuringChainCreation()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $middlewareAlias = '1';
        $config          = [
            'use_cache'         => false,
            'middleware_chains' => [
                'Test' => [
                    $middlewareAlias,
                ],
            ],
            'routing'           => [],
        ];

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = Mockery::mock(MiddlewareChainResolver::class);
        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andThrow(
                new RuntimeException("Middleware with service alias: [{$middlewareAlias}] is not registered as a service")
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(true);

        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(
                $config
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class,
                ]
            )
            ->andReturn(
                new Definition()
            );

        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    RouteNameResolver::class,
                ]
            );
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    CompiledPathResolver::class,
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Middleware with service alias: [{$middlewareAlias}] is not registered as a service");
        $compilerPass->process($container);
    }

    public function testCanHandleNotSimpleMiddlewareServiceProcessing()
    {
        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = Mockery::mock(DeepCopy::class);

        $middlewareAlias = '1';
        $config          = [
            'use_cache'         => false,
            'middleware_chains' => [
                'Test' => [
                    $middlewareAlias,
                ],
            ],
            'routing'           => [],
        ];

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = Mockery::mock(MiddlewareChainResolver::class);
        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andThrow(
                new RuntimeException(
                    "Middleware with service alias: [{$middlewareAlias}] could not be included in chain. Only simple services (without additional calls) could be included"
                )
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::mock(ContainerBuilder::class);
        $container
            ->shouldReceive('hasParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(true);

        $container
            ->shouldReceive('getParameter')
            ->once()
            ->withArgs(
                [
                    'profesia_psr15',
                ]
            )
            ->andReturn(
                $config
            );

        /** @var MockInterface|Definition $adapterDefinition */
        $adapterDefinition = Mockery::mock(Definition::class);
        $container
            ->shouldReceive('getDefinition')
            ->once()
            ->withArgs(
                [
                    SymfonyControllerAdapter::class,
                ]
            )
            ->andReturn(
                $adapterDefinition
            );


        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Middleware with service alias: [{$middlewareAlias}] could not be included in chain. Only simple services (without additional calls) could be included"
        );
        $compilerPass->process($container);
    }

    public function testCanHandleMiddlewareChainsCreation()
    {
        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        [
            'container'     => $container,
            'deepCopy'      => $deepCopy,
            'chainResolver' => $chainResolver,
            'config'        => $config,
        ] = self::setUpStandardContainerExpectations(
            [
                '1',
                '2',
                '3',
            ],
            null
        );

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                [
                    'test' => $definition,
                ]
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        $compilerPass->process($container);
    }

    public function testCanDetectNonExistingMiddlewareChainDuringRoutingRulesCompilation()
    {
        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        [
            'container'     => $container,
            'deepCopy'      => $deepCopy,
            'chainResolver' => $chainResolver,
            'config'        => $config,
        ] = self::setUpStandardContainerExpectations(
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

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                [
                    'test' => $definition,
                ]
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error in condition config: [Condition]. Middleware chain with name: [ABCD] does not exist');
        $compilerPass->process($container);
    }

    public function testCanDetectEmptyConditionsOnRoutingConfig()
    {
        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        [
            'container'     => $container,
            'deepCopy'      => $deepCopy,
            'chainResolver' => $chainResolver,
            'config'        => $config,
        ] = self::setUpStandardContainerExpectations(
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

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                [
                    'Test' => $definition,
                ]
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
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
                        'route_name' => 'test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];

        /** @var array $config */
        $config = $mocks['config'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = $mocks['chainResolver'];

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $middlewareChains = [
            'Test' => $definition
        ];

        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                $middlewareChains
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToPrepend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToAppend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
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
                        'method'     => 'test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];

        /** @var array $config */
        $config = $mocks['config'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = $mocks['chainResolver'];

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $middlewareChains = [
            'Test' => $definition
        ];

        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                $middlewareChains
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToPrepend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToAppend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Error in condition config: [Condition]. Key: 'method' is redundant for condition with 'route_name'");
        $compilerPass->process($container);
    }

    public function testCanRegisterRouteMiddleware()
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
                    ],
                ],
            ]
        );

        /** @var array $config */
        $config = $mocks['config'];

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = $mocks['chainResolver'];

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $middlewareChains = [
            'Test' => $definition
        ];

        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                $middlewareChains
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToPrepend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToAppend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
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
                        $definition,
                    ],
                ]
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        $compilerPass->process($container);
    }

    public function testCanRegisterPathMiddlewareWithoutMethod()
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
                        'path' => '/test',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];

        /** @var array $config */
        $config = $mocks['config'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $middlewareChains = [
            'Test' => $definition
        ];

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = $mocks['chainResolver'];

        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                $middlewareChains
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToPrepend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToAppend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $finalMiddlewareChain = [];
        foreach (ConfigurationHttpMethod::getPossibleValues() as $method) {
            /** @var MockInterface|Definition $httpMethodMiddlewareChain */
            $httpMethodMiddlewareChain = Mockery::mock(Definition::class);
            $httpMethodMiddlewareChain
                ->shouldReceive('setPublic')
                ->once()
                ->withArgs(
                    [
                        false,
                    ]
                )->andReturn(
                    $httpMethodMiddlewareChain
                );

            $httpMethodMiddlewareChain
                ->shouldReceive('setPrivate')
                ->once()
                ->withArgs(
                    [
                        true,
                    ]
                )->andReturn(
                    $httpMethodMiddlewareChain
                );

            $httpMethodMiddlewareChain
                ->shouldReceive('setShared')
                ->once()
                ->withArgs(
                    [
                        false,
                    ]
                )
                ->andReturn(
                    $httpMethodMiddlewareChain
                );

            $deepCopy
                ->shouldReceive('copy')
                ->once()
                ->withArgs(
                    [
                        $definition,
                    ]
                )->andReturn(
                    $httpMethodMiddlewareChain
                );

            $finalMiddlewareChain[$method] = $httpMethodMiddlewareChain;
        }

        /** @var CompiledPathResolver|MockInterface $compiledPathStrategyResolver */
        $compiledPathStrategyResolver = $mocks['compiledPathStrategyResolver'];
        $compiledPathStrategyResolver
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                function (string $methodName, array $parameters) use ($definition, $finalMiddlewareChain) {
                    if ($methodName !== 'registerPathMiddleware') {
                        return false;
                    }

                    /** @var Definition $configurationPathDefinition */
                    $configurationPathDefinition = $parameters[0];
                    $class                       = ConfigurationPath::class;
                    if ($configurationPathDefinition->getFactory() !== [$class, 'createFromConfigurationHttpMethodAndString']) {
                        return false;
                    }

                    $arguments = $configurationPathDefinition->getArguments();
                    if ($arguments[1] !== '/test') {
                        return false;
                    }

                    /** @var Definition $configurationHttpMethod */
                    $configurationHttpMethod = $arguments[0];
                    if ($configurationHttpMethod->getFactory() !== [ConfigurationHttpMethod::class, 'createDefault']) {
                        return false;
                    }

                    if ($configurationHttpMethod->getArguments() !== []) {
                        return false;
                    }

                    if ($parameters[1] !== $finalMiddlewareChain) {
                        return false;
                    }

                    return true;
                }
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        $compilerPass->process($container);
    }

    public function testCanRegisterPathMiddlewareWithMethod()
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
                        'path'   => '/test',
                        'method' => 'GET|POST',
                    ],
                ],
            ]
        );

        /** @var MockInterface|ContainerBuilder $container */
        $container = $mocks['container'];

        /** @var array $config */
        $config = $mocks['config'];

        /** @var MockInterface|DeepCopy $deepCopy */
        $deepCopy = $mocks['deepCopy'];

        /** @var MockInterface|Definition $definition */
        $definition = Mockery::mock(Definition::class);
        $middlewareChains = [
            'Test' => $definition
        ];

        /** @var MockInterface|MiddlewareChainResolver $chainResolver */
        $chainResolver = $mocks['chainResolver'];

        $chainResolver
            ->shouldReceive('resolve')
            ->once()
            ->withArgs(
                [
                    $config['middleware_chains'],
                ]
            )
            ->andReturn(
                $middlewareChains
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToPrepend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        $chainResolver
            ->shouldReceive('resolveMiddlewaresToAppend')
            ->once()
            ->withArgs(
                [
                    $middlewareChains['Test'],
                    [],
                    'Condition',
                ]
            )
            ->andReturn(
                $definition
            );

        /** @var MockInterface|Definition $getMiddlewareChain */
        $getMiddlewareChain = Mockery::mock(Definition::class);
        $getMiddlewareChain
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false,
                ]
            )->andReturn(
                $getMiddlewareChain
            );

        $getMiddlewareChain
            ->shouldReceive('setPrivate')
            ->once()
            ->withArgs(
                [
                    true,
                ]
            )->andReturn(
                $getMiddlewareChain
            );

        $getMiddlewareChain
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false,
                ]
            )
            ->andReturn(
                $getMiddlewareChain
            );

        /** @var MockInterface|Definition $postMiddlewareChain */
        $postMiddlewareChain = Mockery::mock(Definition::class);
        $postMiddlewareChain
            ->shouldReceive('setPublic')
            ->once()
            ->withArgs(
                [
                    false,
                ]
            )->andReturn(
                $postMiddlewareChain
            );

        $postMiddlewareChain
            ->shouldReceive('setPrivate')
            ->once()
            ->withArgs(
                [
                    true,
                ]
            )->andReturn(
                $postMiddlewareChain
            );

        $postMiddlewareChain
            ->shouldReceive('setShared')
            ->once()
            ->withArgs(
                [
                    false,
                ]
            )
            ->andReturn(
                $postMiddlewareChain
            );

        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $definition,
                ]
            )->andReturn(
                $getMiddlewareChain
            );
        $deepCopy
            ->shouldReceive('copy')
            ->once()
            ->withArgs(
                [
                    $definition,
                ]
            )->andReturn(
                $postMiddlewareChain
            );

        $finalMiddlewareChain = [
            'GET'  => $getMiddlewareChain,
            'POST' => $postMiddlewareChain,
        ];

        /** @var CompiledPathResolver|MockInterface $compiledPathStrategyResolver */
        $compiledPathStrategyResolver = $mocks['compiledPathStrategyResolver'];
        $compiledPathStrategyResolver
            ->shouldReceive('addMethodCall')
            ->once()
            ->withArgs(
                function (string $methodName, array $parameters) use ($definition, $finalMiddlewareChain) {
                    if ($methodName !== 'registerPathMiddleware') {
                        return false;
                    }

                    /** @var Definition $configurationPathDefinition */
                    $configurationPathDefinition = $parameters[0];
                    $class                       = ConfigurationPath::class;
                    if ($configurationPathDefinition->getFactory() !== [$class, 'createFromConfigurationHttpMethodAndString']) {
                        return false;
                    }

                    $arguments = $configurationPathDefinition->getArguments();
                    if ($arguments[1] !== '/test') {
                        return false;
                    }

                    /** @var Definition $configurationHttpMethod */
                    $configurationHttpMethod = $arguments[0];
                    if ($configurationHttpMethod->getFactory() !== [ConfigurationHttpMethod::class, 'createFromString']) {
                        return false;
                    }

                    if ($configurationHttpMethod->getArguments() !== ['GET|POST']) {
                        return false;
                    }

                    if ($parameters[1] !== $finalMiddlewareChain
                    ) {
                        return false;
                    }

                    return true;
                }
            );

        $compilerPass = new MiddlewareChainFactoryPass(
            $chainResolver,
            $deepCopy
        );

        $compilerPass->process($container);
    }
}
