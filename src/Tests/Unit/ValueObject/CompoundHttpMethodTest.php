<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use PHPUnit\Framework\Attributes\DataProvider;

class CompoundHttpMethodTest extends TestCase
{
    public static function valuesDataProvider(): array
    {
        return [
            [['GET']],
            [['GET', 'POST']],
            [['GET', 'POST', 'PUT']],
            [['GET', 'POST', 'PUT', 'DELETE']],
        ];
    }

    public function testCanDetectNonValidHttpMethodString()
    {
        $values = [
            'GET',
            'POST',
            'abcd'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String: [abcd] is not a valid value for HttpMethod');
        CompoundHttpMethod::createFromStrings($values);
    }

    /**
     * @param array $values
     */
    #[DataProvider('valuesDataProvider')]
    public function testCanListMethods(array $values)
    {
        $returnValue = CompoundHttpMethod::createFromStrings($values)->listMethods('-delimiter-');
        if (sizeof($values) > 1) {
            $this->assertTrue(strpos($returnValue, '-delimiter-') !== false);
        }

        $exploded = explode('-delimiter-', $returnValue);
        $this->assertCount(sizeof($values), $exploded);

        foreach ($exploded as $index => $item) {
            $this->assertEquals($values[$index], $item);
        }
    }

    public function testCanDetectEmptiness()
    {
        $this->assertTrue(
            CompoundHttpMethod::createFromStrings([])->isEmpty()
        );

        $this->assertFalse(
            CompoundHttpMethod::createFromStrings(['GET'])->isEmpty()
        );
    }
}