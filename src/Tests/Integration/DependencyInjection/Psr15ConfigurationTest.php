<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Psr15Configuration;
use Symfony\Component\Config\Definition\Processor;
use PHPUnit\Framework\Attributes\DataProvider;

class Psr15ConfigurationTest extends TestCase
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
    public function testCanProcess(array $configs): void
    {
        $processor = new Processor();

        $pickedUpConfigs = [
            $configs[0],
        ];
        $config          = $processor->processConfiguration(
            new Psr15Configuration(),
            $pickedUpConfigs
        );

        $configToTest = $configs[0];
        $this->assertEquals($config, $configToTest);
    }

    /**
     * @param array $configs
     * @return void
     */
    #[DataProvider('provideConfigsData')]
    public function testCanOverrideConfigCorrectly(array $configs): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(
            new Psr15Configuration(),
            $configs
        );

        $overrideConfig = $configs[1];
        $this->assertEquals($config['use_cache'], $overrideConfig['use_cache']);
        $this->assertEquals($config['middleware_chains'], $overrideConfig['middleware_chains']);
        $this->assertEquals($config['routing'], $overrideConfig['routing']);
    }
}