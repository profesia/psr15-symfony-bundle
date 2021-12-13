<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;

abstract class MockeryTestCase extends TestCase
{
    protected function tearDown(): void
    {
        $this->addToAssertionCount(
            Mockery::getContainer()->mockery_getExpectationCount()
        );

        parent::tearDown();
        Mockery::close();
    }
}
