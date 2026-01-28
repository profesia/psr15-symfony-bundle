<?php

declare(strict_types=1);

namespace Integration\Middleware;

use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestRequestHandler;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware3;
use Psr\Http\Server\MiddlewareInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class MiddlewareCollectionTest extends TestCase
{
    public static function nullCheckDataProvider(): array
    {
        return [
            [
                new TestMiddleware1(),
                false,
            ],
            [
                new NullMiddleware(),
                true,
            ],
        ];
    }

    /**
     * @param MiddlewareInterface $middleware
     * @param bool                $assertValue
     */
    #[DataProvider('nullCheckDataProvider')]
    public function testCanDetectNullMiddleware(MiddlewareInterface $middleware, bool $assertValue)
    {
        $collection = new MiddlewareCollection(
            [
                $middleware,
            ]
        );
        $this->assertEquals($assertValue, $collection->isNullMiddleware());
    }

    public static function listClassNamesDataProvider(): array
    {
        $m1 = new TestMiddleware1();
        $m2 = new TestMiddleware2();
        $m3 = new TestMiddleware3();
        $n1 = get_class($m1);
        $n2 = get_class($m2);
        $n3 = get_class($m3);

        return [
            [
                [
                    $m1,
                ],
                [
                    $n1,
                ],
            ],
            [
                [
                    $m1,
                    $m3,
                ],
                [
                    $n1,
                    $n3,
                ],
            ],
            [
                [
                    $m1,
                    $m3,
                    $m2,
                ],
                [
                    $n1,
                    $n3,
                    $n2,
                ],
            ],
        ];
    }

    /**
     * @param array $middlewares
     * @param array $classNames
     */
    #[DataProvider('listClassNamesDataProvider')]
    public function testCanListMiddlewareClassNames(array $middlewares, array $classNames)
    {
        $collection = new MiddlewareCollection(
            $middlewares
        );

        $this->assertEquals($classNames, $collection->listClassNames());
    }

    public function testThrowExceptionOnEmptyConstructorMiddlewaresArray()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('It is redundant to create MiddlewareChainHandler from empty array of middlewares');
        new MiddlewareCollection([]);
    }

    public static function provideDataForChange(): array
    {
        return [
            [
                [
                    TestMiddleware2::class,
                    TestMiddleware1::class,
                    TestMiddleware3::class,
                    TestMiddleware1::class,
                ],
            ],
        ];
    }

    /**
     * @param array $namesToPrepend
     */
    #[DataProvider('provideDataForChange')]
    public function testCanPrepend(array $namesToPrepend)
    {
        $collection = new MiddlewareCollection(
            [
                new TestMiddleware1(),
                new TestMiddleware2(),
                new TestMiddleware3(),
            ]
        );

        $names = [
            TestMiddleware1::class,
            TestMiddleware2::class,
            TestMiddleware3::class,
        ];

        $this->assertEquals($names, $collection->listClassNames());

        foreach ($namesToPrepend as $middlewareToPrepend) {
            $collection = $collection->prepend(
                new $middlewareToPrepend()
            );

            array_unshift($names, $middlewareToPrepend);

            $this->assertEquals($names, $collection->listClassNames());
        }
    }

    /**
     * @param array $namesToAppend
     */
    #[DataProvider('provideDataForChange')]
    public function testCanAppend(array $namesToAppend)
    {
        $collection = new MiddlewareCollection(
            [
                new TestMiddleware1(),
                new TestMiddleware2(),
                new TestMiddleware3(),
            ]
        );

        $names = [
            TestMiddleware1::class,
            TestMiddleware2::class,
            TestMiddleware3::class,
        ];

        $this->assertEquals($names, $collection->listClassNames());

        foreach ($namesToAppend as $middlewareToAppend) {
            $collection = $collection->append(
                new $middlewareToAppend()
            );

            $names[] = $middlewareToAppend;

            $this->assertEquals($names, $collection->listClassNames());
        }
    }

    /**
     * @param array $dataToChange
     */
    #[DataProvider('provideDataForChange')]
    public function testCanAppendCollection(array $dataToChange)
    {
        $collection = new MiddlewareCollection(
            [
                new TestMiddleware1(),
                new TestMiddleware2(),
                new TestMiddleware3(),
            ]
        );

        $names = [
            TestMiddleware1::class,
            TestMiddleware2::class,
            TestMiddleware3::class,
        ];

        $this->assertEquals($names, $collection->listClassNames());

        $collection = $collection->appendCollection(
            new MiddlewareCollection(
                array_map(function (string $className) {
                    return new $className();
                }, $dataToChange)
            )
        );

        $this->assertEquals(
            array_merge($names, $dataToChange),
            $collection->listClassNames()
        );
    }

    public function testCanTransformToMiddlewareChain()
    {
        $collection = new MiddlewareCollection(
            [
                new TestMiddleware1(),
                new TestMiddleware2(),
                new TestMiddleware3(),
            ]
        );

        $handler = new TestRequestHandler();

        $collection->transformToMiddlewareChainHandler($handler);
        $this->assertTrue(true);
    }
}
