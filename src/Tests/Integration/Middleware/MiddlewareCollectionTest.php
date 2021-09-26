<?php

declare(strict_types=1);

namespace Integration\Middleware;

use PHPUnit\Framework\TestCase;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware3;
use Psr\Http\Server\MiddlewareInterface;

class MiddlewareCollectionTest extends TestCase
{
    public function nullCheckDataProvider(): array
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
     *
     * @dataProvider nullCheckDataProvider
     */
    public function testCanDetectNullMiddleware(MiddlewareInterface $middleware, bool $assertValue)
    {
        $collection = new MiddlewareCollection(
            [
                $middleware,
            ]
        );
        $this->assertEquals($assertValue, $collection->isNullMiddleware());
    }

    public function listClassNamesDataProvider()
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
                    $m2
                ],
                [
                    $n1,
                    $n3,
                    $n2
                ],
            ]
        ];
    }

    /**
     * @param array $middlewares
     * @param array $classNames
     *
     * @dataProvider listClassNamesDataProvider
     */
    public function testCanListMiddlewareClassNames(array $middlewares, array $classNames)
    {
        $collection = new MiddlewareCollection(
            $middlewares
        );

        $this->assertEquals($classNames, $collection->listClassNames());
    }
}
