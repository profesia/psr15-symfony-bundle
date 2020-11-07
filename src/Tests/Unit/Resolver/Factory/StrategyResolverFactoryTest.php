<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Factory;

use Profesia\Symfony\Psr15Bundle\Resolver\Factory\StrategyResolverFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;

class StrategyResolverFactoryTest extends MockeryTestCase
{
    public function testCanCreate()
    {
        $factory = new StrategyResolverFactory();

        /** @var MockInterface|AbstractChainResolver $strategy2 */
        $strategy2 = Mockery::mock(AbstractChainResolver::class);

        /** @var MockInterface|AbstractChainResolver $strategy1 */
        $strategy1 = Mockery::mock(AbstractChainResolver::class);
        $strategy1
            ->shouldReceive('setNext')
            ->once()
            ->withArgs(
                [
                    $strategy2
                ]
            );

        $inputArray = [
            $strategy1,
            $strategy2
        ];

        $chain = $factory->create($inputArray);
        $this->assertEquals($strategy1, $chain);
    }
}