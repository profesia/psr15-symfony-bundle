<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use InvalidArgumentException;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use PHPUnit\Framework\Attributes\DataProvider;

class ResolvedMiddlewareAccessKeyTest extends MockeryTestCase
{
    public static function exceptionDataProvider(): array
    {
        return [
            [[], InvalidArgumentException::class, 'Key: [accessPath] is not present in input argument'],
            [['accessPath' => []], InvalidArgumentException::class, 'Key: [resolverClass] is not present in input argument'],
            [['accessPath' => [], 'resolverClass' => 'Test123'], InvalidArgumentException::class, 'Resolver: [Test123] is not supported']
        ];
    }

    /**
     * @param array  $array
     * @param string $exceptionClass
     * @param string $exceptionMessage
     */
    #[DataProvider('exceptionDataProvider')]
    public function testWillDetectInvalidArrayConfiguration(array $array, string $exceptionClass, string $exceptionMessage)
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        ResolvedMiddlewareAccessKey::createFromArray($array);
    }
}