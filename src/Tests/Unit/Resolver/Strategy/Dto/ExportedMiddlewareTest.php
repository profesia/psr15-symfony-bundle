<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy\Dto;

use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Tests\MockeryTestCase;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Mockery;
use Mockery\MockInterface;

class ExportedMiddlewareTest extends MockeryTestCase
{
    public function testCanListMiddlewareChainItems()
    {
        $classNames = [
            'Test1',
            'Test2',
            'Test3'
        ];

        /** @var MiddlewareCollection|MockInterface $chain */
        $chain = Mockery::mock(MiddlewareCollection::class);
        $chain
            ->shouldReceive('listClassNames')
            ->once()
            ->andReturn(
                $classNames
            );

        $compoundHttpMethod = CompoundHttpMethod::createFromStrings(['GET']);
        $exportedMiddleware = new ExportedMiddleware(
            $chain,
            $compoundHttpMethod,
            '/test',
            'test'
        );

        $this->assertEquals(
            $classNames,
            $exportedMiddleware->listMiddlewareChainItems()
        );
    }

    public function testCanGetIdentifier()
    {
        /** @var MiddlewareCollection|MockInterface $chain */
        $chain              = Mockery::mock(MiddlewareCollection::class);
        $compoundHttpMethod = CompoundHttpMethod::createFromStrings(['GET']);

        $exportedMiddleware = new ExportedMiddleware(
            $chain,
            $compoundHttpMethod,
            '/test1',
        );
        $this->assertEquals('/test1', $exportedMiddleware->getIdentifier());

        $exportedMiddleware = new ExportedMiddleware(
            $chain,
            $compoundHttpMethod,
            '/test2',
            'test2'
        );
        $this->assertEquals('test2', $exportedMiddleware->getIdentifier());
    }

    public function testCanGetHttpMethods()
    {
        /** @var MiddlewareCollection|MockInterface $chain */
        $chain              = Mockery::mock(MiddlewareCollection::class);

        /** @var CompoundHttpMethod|MockInterface $compoundHttpMethodMock */
        $compoundHttpMethodMock = Mockery::mock(CompoundHttpMethod::class);
        $compoundHttpMethodMock
            ->shouldReceive('isEmpty')
            ->once()
            ->andReturn(
                true
            );

        $exportedMiddleware = new ExportedMiddleware(
            $chain,
            $compoundHttpMethodMock,
            '/test',
        );

        $expectedCompoundHttpMethod = CompoundHttpMethod::createFromStrings(
            HttpMethod::getPossibleValues()
        );

        $this->assertEquals($expectedCompoundHttpMethod, $exportedMiddleware->getHttpMethods());

        $compoundHttpMethod = CompoundHttpMethod::createFromStrings(['GET', 'POST']);
        /** @var CompoundHttpMethod|MockInterface $compoundHttpMethodMock */
        $compoundHttpMethodMock = Mockery::mock($compoundHttpMethod);
        $compoundHttpMethodMock
            ->shouldReceive('isEmpty')
            ->once()
            ->andReturn(
                false
            );

        $exportedMiddleware = new ExportedMiddleware(
            $chain,
            $compoundHttpMethodMock,
            '/test',
        );

        $this->assertEquals('GET | POST', $exportedMiddleware->getHttpMethods()->listMethods(' | '));
    }
}
