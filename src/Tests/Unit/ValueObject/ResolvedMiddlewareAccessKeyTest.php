<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use InvalidArgumentException;
use Mockery;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;

class ResolvedMiddlewareAccessKeyTest extends MockeryTestCase
{
    public function exceptionDataProvider()
    {
        return [
            [[], InvalidArgumentException::class, 'Key: [accessPath] is not present in input argument'],
            [['accessPath' => []], InvalidArgumentException::class, 'Key: [resolverClass] is not present in input argument'],
        ];
    }

    /**
     * @dataProvider exceptionDataProvider
     *
     * @param array  $array
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    public function testWillDetectInvalidArrayConfiguration(array $array, string $exceptionClass, string $exceptionMessage)
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        ResolvedMiddlewareAccessKey::createFromArray($array);
    }

    /*public function testCanCreateFromArray()
    {
    }*/

    /*public function testCanTransformToArray()
    {
        $mock  = Mockery::mock(RouteNameResolver::class);
        $array = [
            'resolverClass' => get_class($mock),
            'accessPath'    => [
                'a',
                'b',
                'c'
            ]
        ];
        $vo    = ResolvedMiddlewareAccessKey::createFromArray(
            $array
        );
        $this->assertEquals(
            $array,
            $vo->toArray()
        );
    }*/

}