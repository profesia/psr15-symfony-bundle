<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Tests\Unit\ValueObject;

use Delvesoft\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class CompoundHttpMethodTest extends TestCase
{
    public function testCanDetectNonValidHttpMethodString()
    {
        $values = [
            'GET',
            'POST',
            'abcd'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String: [abcd] is not a valid value for Configuration HTTP method');
        CompoundHttpMethod::createFromStrings($values);
    }
    
    public function testCanListMethods()
    {
        $values = [
            'GET',
            'POST',
            'PUT'
        ];
        
        $returnValue = CompoundHttpMethod::createFromStrings($values)->listMethods('-delimiter-');
        $this->assertTrue(strpos($returnValue, '-delimiter-') !== false);
        $exploded = explode('-delimiter-', $returnValue);
        $this->assertCount(3, $exploded);

        foreach ($exploded as $index => $item) {
            $this->assertEquals($values[$index], $item);
        }
    }
}