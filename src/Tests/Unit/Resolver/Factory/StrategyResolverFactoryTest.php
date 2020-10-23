<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Factory;

use Profesia\Symfony\Psr15Bundle\Resolver\Factory\StrategyResolverFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolverItem;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class StrategyResolverFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanCreate()
    {
        $factory = new StrategyResolverFactory();

        /** @var MockInterface|AbstractChainResolverItem $strategy2 */
        $strategy2 = Mockery::mock(AbstractChainResolverItem::class);

        /** @var MockInterface|AbstractChainResolverItem $strategy1 */
        $strategy1 = Mockery::mock(AbstractChainResolverItem::class);
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