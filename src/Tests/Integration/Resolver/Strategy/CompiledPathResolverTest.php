<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\Resolver\Strategy;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\InvalidAccessKeyException;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

class CompiledPathResolverTest extends MockeryTestCase
{
    public function testCanCheckMultipleRegisteredRulesTillTheMatchingOneIsFound()
    {
        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

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

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1',
            ),
            [
                'GET' => $nullMiddleware,
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/12',
            ),
            [
                'GET'  => $nullMiddleware,
                'POST' => $nullMiddleware,
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/123',
            ),
            [
                'GET'  => $nullMiddleware,
                'POST' => $nullMiddleware,
                'PUT'  => $nullMiddleware,
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1234',
            ),
            [
                'GET'    => $nullMiddleware,
                'POST'   => $nullMiddleware,
                'PUT'    => $nullMiddleware,
                'DELETE' => $nullMiddleware,
            ]
        );

        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        $this->assertTrue($nullMiddleware === $resolvedMiddlewareChain->getMiddlewareChain());
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
        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

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

        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/1',
            ),
            [
                'GET' => $nullMiddleware,
            ]
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET'),
                '/ab',
            ),
            [
                'GET'  => $nullMiddleware,
                'POST' => $nullMiddleware,
            ]
        );


        $resolvedMiddlewareChain = $resolver->handle($middlewareResolvingRequest);
        $this->assertTrue($resolvedMiddlewareChain->isNullMiddleware());
        $this->assertEquals($nullMiddleware, $resolvedMiddlewareChain->getMiddlewareChain());
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
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

        $this->assertEmpty($resolver->exportRules());

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

        $resolver->registerPathMiddleware(
            ConfigurationPath::createFromConfigurationHttpMethodAndString(
                ConfigurationHttpMethod::createFromString('GET|POST'),
                '/sk',
            ),
            [
                'GET'  => $nullMiddleware,
                'POST' => $nullMiddleware,
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

    public function getChainDataProvider()
    {
        $class = CompiledPathResolver::class;

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

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
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

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
        /** @var MockInterface|MiddlewareChainItemFactory $middlewareChainItemFactory */
        $middlewareChainItemFactory = Mockery::mock(MiddlewareChainItemFactory::class);

        $resolver = new CompiledPathResolver(
            $middlewareChainItemFactory
        );

        $path = ConfigurationPath::createFromConfigurationHttpMethodAndString(
            ConfigurationHttpMethod::createFromString('GET'),
            '/1',
        );

        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $nullMiddleware = new NullMiddleware(
            $serverRequestFactory,
            $responseFactory
        );

        $resolver->registerPathMiddleware(
            $path,
            [
                'GET' => $nullMiddleware,
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

        $this->assertEquals($nullMiddleware, $resolver->getChain($accessKey));
    }
}