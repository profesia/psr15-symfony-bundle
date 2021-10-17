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

class PathMiddlewareTestCase extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        $fileSystem = new Filesystem();
        if ($fileSystem->exists(__DIR__ . '/Assets/cache')) {
            $fileSystem->remove(__DIR__ . '/Assets/cache');
        }
    }

    public function provideDataForIntegrationTest()
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
                ],
            ]
        );
        $kernel->boot();

        return [
            [
                $kernel,
                ['POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
                '/1',
                '',
            ],
            [
                $kernel,
                ['GET'],
                '/1',
                '2 1 1 2 3 3 1',
            ],
            [
                $kernel,
                ['GET'],
                '/2',
                '1 2 3',
            ],
            [
                $kernel,
                ['GET', 'POST', 'PUT', 'DELETE'],
                '/3',
                '1 1 1 2 3',
            ],
            [
                $kernel,
                ['DELETE'],
                '/2',
                '1 2 3 3 1 3',
            ],
            [
                $kernel,
                ['POST'],
                '/2',
                '1 2 3 1 2 3 3 1 3',
            ],
        ];
    }

    /**
     * @param KernelInterface $kernel
     * @param string[]        $methods
     * @param string          $route
     * @param string          $toCompare
     *
     * @dataProvider provideDataForIntegrationTest
     */
    public function testPathMiddlewares(KernelInterface $kernel, array $methods, string $route, string $toCompare)
    {
        $client = new KernelBrowser(
            $kernel
        );
        $client->catchExceptions(false);

        foreach ($methods as $method) {
            $client->request($method, $route);
            $response = $client->getResponse();

            $this->assertEquals(200, $response->getStatusCode());
            $content = json_decode($response->getContent(), true);
            $this->assertEquals($toCompare, $content['headers']);
        }
    }
}
