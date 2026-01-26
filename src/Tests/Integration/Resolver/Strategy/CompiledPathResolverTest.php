<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\Resolver\Strategy;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\InvalidAccessKeyException;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

class CompiledPathResolverTest extends MockeryTestCase
{
    public function testCanCheckMultipleRegisteredRulesTillTheMatchingOneIsFound()
    {
        $nullMiddleware = new NullMiddleware();

        $routeName = 'test';
        $request   = new Request(
            [],
            [],
            []
        );

        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->times(2)
            ->andReturn(
                '/12'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );

        $resolver = new CompiledPathResolver();

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1',
            ),
            [
                'GET' => new MiddlewareCollection([$nullMiddleware]),
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/12',
            ),
            [
                'GET'  => new MiddlewareCollection([$nullMiddleware]),
                'POST' => new MiddlewareCollection([$nullMiddleware]),
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/123',
            ),
            [
                'GET'  => new MiddlewareCollection([$nullMiddleware]),
                'POST' => new MiddlewareCollection([$nullMiddleware]),
                'PUT'  => new MiddlewareCollection([$nullMiddleware]),
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1234',
            ),
            [
                'GET'    => new MiddlewareCollection([$nullMiddleware]),
                'POST'   => new MiddlewareCollection([$nullMiddleware]),
                'PUT'    => new MiddlewareCollection([$nullMiddleware]),
                'DELETE' => new MiddlewareCollection([$nullMiddleware]),
            ]
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        $this->assertTrue($resolvedMiddlewareChain->getMiddlewareAccessKey()->isSameResolver($resolver));
        $this->assertEquals(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    3,
                    '/12',
                    'GET'
                ],
            ],
            $resolvedMiddlewareChain->getMiddlewareAccessKey()->toArray()
        );
    }

    public function testCAnChooseMatchLessStrictRule()
    {
        $nullMiddleware = new NullMiddleware();

        $routeName = 'test';
        $request   = new Request(
            [],
            [],
            []
        );

        /** @var MockInterface|CompiledRoute $compiledRoute */
        $compiledRoute = Mockery::mock(CompiledRoute::class);
        $compiledRoute
            ->shouldReceive('getStaticPrefix')
            ->times(2)
            ->andReturn(
                '/12'
            );

        /** @var MockInterface|Route $route */
        $route = Mockery::mock(Route::class);
        $route
            ->shouldReceive('compile')
            ->once()
            ->andReturn(
                $compiledRoute
            );

        $middlewareResolvingRequest = MiddlewareResolvingRequest::createFromFoundationAssets(
            $request,
            $route,
            $routeName
        );

        $resolver = new CompiledPathResolver();

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1',
            ),
            [
                'GET' => new MiddlewareCollection([$nullMiddleware]),
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/ab',
            ),
            [
                'GET'  => new MiddlewareCollection([$nullMiddleware]),
                'POST' => new MiddlewareCollection([$nullMiddleware]),
            ]
        );


        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        $this->assertTrue($resolvedMiddlewareChain->getMiddlewareAccessKey()->isSameResolver($resolver));
        $this->assertEquals(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath'    => [
                    2,
                    '/1',
                    'GET'
                ],
            ],
            $resolvedMiddlewareChain->getMiddlewareAccessKey()->toArray()
        );
    }

    public function testCanExport()
    {
        $resolver = new CompiledPathResolver();

        $this->assertEmpty($resolver->exportRules());

        $nullMiddleware = new NullMiddleware();

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET|POST'),
                '/sk',
            ),
            [
                'GET'  => new MiddlewareCollection([$nullMiddleware]),
                'POST' => new MiddlewareCollection([$nullMiddleware]),
            ]
        );

        $exportedRules = $resolver->exportRules();
        $this->assertNotEmpty($exportedRules);
        $this->assertCount(1, $exportedRules);

        /** @var ExportedMiddleware $exportedMiddleware */
        $exportedMiddleware = current($exportedRules);
        $this->assertEquals('/sk', $exportedMiddleware->getIdentifier());
        $this->assertEquals('GET|POST', $exportedMiddleware->getHttpMethods()->listMethods('|'));
    }

    public static function getChainDataProvider(): array
    {
        $class = CompiledPathResolver::class;
        $nullMiddleware = new NullMiddleware();

        return [
            [
                InvalidAccessKeyException::class,
                "Bad access keys: [1] in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            '1'
                        ]
                    ]
                ),
                [

                ]
            ],
            [
                InvalidAccessKeyException::class,
                "Bad access keys: [1, 2] in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            '1',
                            '2'
                        ]
                    ]
                ),
                [

                ]
            ],
            [
                ChainNotFoundException::class,
                "Key: [1] was not found in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            '1',
                            '/',
                            'GET'
                        ]
                    ]
                ),
                [

                ]
            ],
            [
                ChainNotFoundException::class,
                "Key: [1] was not found in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            '1',
                            '/',
                            'GET'
                        ]
                    ]
                ),
                [

                ]
            ],
            [
                ChainNotFoundException::class,
                "Keys: [2, /1] was not found in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            '2',
                            '/1',
                            'GET'
                        ]
                    ]
                ),
                [
                    [
                        'path'   => ConfigurationPath::createFromConfigurationHttpMethodAndString(
                            ConfigurationHttpMethod::createFromString('GET'),
                            '/a',
                        ),
                        'chains' => [
                            'GET' => $nullMiddleware
                        ]
                    ]
                ]
            ],
            [
                ChainNotFoundException::class,
                "Chain with key: [2, /1, POST] was not found in resolver: [{$class}]",
                ResolvedMiddlewareAccessKey::createFromArray(
                    [
                        'resolverClass' => $class,
                        'accessPath'    => [
                            '2',
                            '/1',
                            'POST'
                        ]
                    ]
                ),
                [
                    [
                        'path'   => ConfigurationPath::createFromConfigurationHttpMethodAndString(
                            ConfigurationHttpMethod::createFromString('GET'),
                            '/1',
                        ),
                        'chains' => [
                            'GET' => $nullMiddleware
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider getChainDataProvider
     *
     * @param string                      $exceptionClass
     * @param string                      $exceptionMessage
     * @param ResolvedMiddlewareAccessKey $accessKey
     * @param array                       $rulesToRegister
     */
    public function testCanThrowExceptionDuringGettingOfChain(
        string $exceptionClass,
        string $exceptionMessage,
        ResolvedMiddlewareAccessKey $accessKey,
        array $rulesToRegister
    ) {
        $resolver = new CompiledPathResolver();

        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);

        foreach ($rulesToRegister as $rule) {
            $resolver->registerPathMiddleware(
                $rule['path'],
                $rule['chains']
            );
        }

        $resolver->getChain($accessKey);
    }

    public function testCanGetMiddlewareChain()
    {
        $resolver = new CompiledPathResolver();

        $path = ConfigurationPath::createFromConfigurationHttpMethodAndString(
            ConfigurationHttpMethod::createFromString('GET'),
            '/1',
        );

        $nullMiddleware = new NullMiddleware();

        $resolver->registerPathMiddleware(
            $path,
            [
                'GET' => new MiddlewareCollection([$nullMiddleware]),
            ]
        );

        $accessKey = ResolvedMiddlewareAccessKey::createFromArray(
            [
                'resolverClass' => CompiledPathResolver::class,
                'accessPath' => [
                    2,
                    '/1',
                    'GET'
                ],
            ]
        );

        $resolvedMiddleware = ResolvedMiddlewareChain::createFromResolverContext(
            new MiddlewareCollection([$nullMiddleware]),
            $accessKey
        );
        $this->assertEquals($resolvedMiddleware, $resolver->getChain($accessKey));
    }
}
