<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Acceptance;

use PHPUnit\Framework\TestCase;
use Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets\TestKernel;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware3;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class PathMiddlewareTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(__DIR__ . '/Assets/cache')) {
            $fileSystem->remove(__DIR__ . '/Assets/cache');
        }
    }

    public static function provideDataForIntegrationTest(): array
    {
        $kernel = new TestKernel(
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
                        'append'           => [
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
                    '5' => [
                        'middleware_chain' => 'FirstChain',
                        'conditions'       => [
                            [
                                'path'   => '/4',
                                'method' => 'POST',
                            ],
                        ],
                    ],
                    '6' => [
                        'middleware_chain' => 'FirstChain',
                        'conditions'       => [
                            [
                                'path'   => '/5',
                                'method' => 'GET',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $kernel->boot();

        return [
            'test-not-matching-http-method' => [
                $kernel,
                ['POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
                '/1',
                '',
            ],
            'test-matching-http-method' => [
                $kernel,
                ['GET'],
                '/1',
                '2 1 1 2 3 3 1',
            ],
            'test-match' => [
                $kernel,
                ['GET'],
                '/2',
                '1 2 3',
            ],
            'test-match-prepend' => [
                $kernel,
                ['GET', 'POST', 'PUT', 'DELETE'],
                '/3',
                '1 1 1 2 3',
            ],
            'test-match-append' => [
                $kernel,
                ['DELETE'],
                '/2',
                '1 2 3 3 1 3',
            ],
            'test-match-prepend-and-append' => [
                $kernel,
                ['POST'],
                '/2',
                '1 2 3 1 2 3 3 1 3',
            ],
            'test-match-localized-path' => [
                $kernel,
                ['POST'],
                '/4',
                '1 2 3',
            ],
            'test-match-localized-route-without-localized-path' => [
                $kernel,
                ['GET'],
                '/5',
                '1 2 3',
            ],
        ];
    }

    /**
     * @param KernelInterface $kernel
     * @param string[]        $methods
     * @param string          $route
     * @param string          $toCompare
     */
    #[DataProvider('provideDataForIntegrationTest')]
    public function testPathMiddlewares(KernelInterface $kernel, array $methods, string $route, string $toCompare)
    {
        $client = new KernelBrowser(
            $kernel
        );
        $client->catchExceptions(true);

        foreach ($methods as $method) {
            $client->request($method, $route);
            $response = $client->getResponse();

            $this->assertEquals(200, $response->getStatusCode());
            $content = json_decode($response->getContent(), true);
            $this->assertEquals($toCompare, $content['headers']);
        }
    }
}
