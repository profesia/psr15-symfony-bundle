<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Integration\DependencyInjection\Compiler;

use Mockery;
use Mockery\MockInterface;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler\MiddlewareChainResolver;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MiddlewareChainResolverTest extends MockeryTestCase
{
    public static function provideDataForUnchangedTest(): array
    {
        return [
            [
                'resolveMiddlewaresToPrepend'
            ],
            [
                'resolveMiddlewaresToAppend'
            ]
        ];
    }

    /**
     * @dataProvider provideDataForUnchangedTest
     */
    public function testChainRemainsUnchangedOnEmptyValues(string $methodName)
    {
        /** @var ContainerBuilder|MockInterface $container */
        $container = Mockery::mock(ContainerBuilder::class);

        $resolver = new MiddlewareChainResolver(
            $container
        );

        $definition = new Definition();
        $resolvedDefinition = $resolver->{$methodName}(
            $definition,
            [],
            'Test'
        );

        $this->assertEquals($definition, $resolvedDefinition);
        $this->assertEquals($definition->getClass(), $resolvedDefinition->getClass());
        $this->assertEquals($definition->getMethodCalls(), $resolvedDefinition->getMethodCalls());
    }
}
