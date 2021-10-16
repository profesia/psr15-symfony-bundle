<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets;

use Nyholm\Psr7\Factory\Psr17Factory;
use Profesia\Symfony\Psr15Bundle\ProfesiaPsr15Bundle;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware3;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return [
            new ProfesiaPsr15Bundle(),
            new FrameworkBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container)
    {
        $container->services()->set(
            TestMiddleware1::class,
        );

        $container->services()->set(
            TestMiddleware2::class,
        );

        $container->services()->set(
            TestMiddleware3::class,
        );

        $container->services()->set(
            'nyholm.psr7.psr17_factory',
            Psr17Factory::class
        );

        $container->extension(
            'profesia_psr15',
            [
                'use_cache'         => false,
                'middleware_chains' => [
                    'FirstChain' => [
                        TestMiddleware1::class,
                        TestMiddleware2::class,
                        TestMiddleware3::class,
                    ],
                ],
                'routing'           => [
                    '1' => [
                        'middleware_chain' => 'FirstChain',
                        'prepend'          => [
                            TestMiddleware2::class,
                            TestMiddleware1::class,
                        ],
                        'append'           => [
                            TestMiddleware3::class,
                            TestMiddleware1::class,
                        ],
                        'conditions'       => [
                            [
                                'path'   => '/1',
                                'method' => 'GET',
                            ],
                        ],
                    ],
                    '2' => [
                        'middleware_chain' => 'FirstChain',
                        'conditions'       => [
                            [
                                'path'   => '/2',
                                'method' => 'GET|POST',
                            ],
                        ],
                    ],
                    '3' => [
                        'middleware_chain' => 'FirstChain',
                        'prepend'          => [
                            TestMiddleware1::class,
                            TestMiddleware1::class,
                        ],
                        'conditions'       => [
                            [
                                'path'   => '/3',
                                'method' => 'GET|POST|PUT|DELETE',
                            ],
                        ],
                    ],
                    '4' => [
                        'middleware_chain' => 'FirstChain',
                        'append' => [
                            TestMiddleware3::class,
                            TestMiddleware1::class,
                            TestMiddleware3::class,
                        ],
                        'conditions'       => [
                            [
                                'path'   => '/2',
                                'method' => 'POST|DELETE',
                            ],
                        ],
                    ],
                ],
            ]
        );
    }


    protected function configureRoutes(RoutingConfigurator $routes)
    {
        $routes->import(__DIR__ . '/../../../Resources/config/test-routes.xml');
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/cache/' . spl_object_hash($this);
    }


}
