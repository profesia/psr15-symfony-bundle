<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\DependencyInjection;

use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Psr15Configuration;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Psr15Extension;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Psr15ExtensionTest extends MockeryTestCase
{
    public static function provideConfigsData(): array
    {
        return [
            [
                [
                    [
                        "use_cache"         => true,
                        "middleware_chains" => [
                            "MiddlewareChain1" => [
                                "Middleware1",
                                "Middleware2",
                                "Middleware3",
                                "Middleware4",
                            ]
                        ],
                        "routing"           => [
                            "RoutingRules1" => [
                                "middleware_chain" => "MiddlewareChain1",
                                "conditions"       => [
                                    [
                                        "path"   => "/test-url/api/v1",
                                        "method" => "POST"
                                    ]
                                ],
                                'prepend'          => [
                                    'Middleware10',
                                    'Middleware11',
                                    'Middleware12',
                                ],
                                'append'           => [
                                    'Middleware13',
                                    'Middleware14',
                                    'Middleware15',
                                ]
                            ]
                        ]
                    ],
                    [
                        "use_cache"         => false,
                        "middleware_chains" => [
                            "MiddlewareChain1" => [
                                "Middleware5",
                                "Middleware6",
                                "Middleware7",
                                "Middleware8",
                                "Middleware9",
                            ]
                        ],
                        "routing"           => [
                            "RoutingRules1" => [
                                "middleware_chain" => "MiddlewareChain2",
                                "conditions"       => [
                                    [
                                        "path"   => "/test-url/api/v2",
                                        "method" => "PUT"
                                    ]
                                ],
                                'prepend'          => [
                                    'Middleware16',
                                    'Middleware17',
                                    'Middleware18',
                                ],
                                'append'           => [
                                    'Middleware19',
                                    'Middleware20',
                                    'Middleware21',
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $configs
     * @return void
     */
    #[DataProvider('provideConfigsData')]
    public function testCanLoadExtension(array $configs): void
    {
        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::spy(ContainerBuilder::class);

        $processor = new Processor();
        $finalConfig = $processor->processConfiguration(new Psr15Configuration(), $configs);
        $container
            ->shouldReceive('setParameter')
            ->once()
            ->withArgs(function(string $key, array $mergedConfig) use ($finalConfig) {
                return ($mergedConfig === $finalConfig);
            });

        $extension = new Psr15Extension();
        $extension->load($configs, $container);
    }

    /**
     * @param array $configs
     * @return void
     */
    #[DataProvider('provideConfigsData')]
    public function testCanLoadExtensionOnEmptyMainConfig(array $configs): void
    {
        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::spy(ContainerBuilder::class);
        $configs[0] = [];

        $processor = new Processor();
        $finalConfig = $processor->processConfiguration(new Psr15Configuration(), $configs);
        $container
            ->shouldReceive('setParameter')
            ->once()
            ->withArgs(function(string $key, array $mergedConfig) use ($finalConfig) {
                return ($mergedConfig === $finalConfig);
            });

        $extension = new Psr15Extension();
        $extension->load($configs, $container);
    }

    public function testWillNotBootOnEmptyConfigs(): void
    {
        /** @var MockInterface|ContainerBuilder $container */
        $container = Mockery::spy(ContainerBuilder::class);

        $configs = [[], []];
        $container
            ->shouldNotReceive('setParameter');

        $extension = new Psr15Extension();
        $extension->load($configs, $container);
    }
}
