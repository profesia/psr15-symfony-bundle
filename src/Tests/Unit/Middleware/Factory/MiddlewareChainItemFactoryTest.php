<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Middleware\Factory;

use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

class MiddlewareChainItemFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanCreateInstanceByClassName()
    {
        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $factory = new MiddlewareChainItemFactory(
            $serverRequestFactory,
            $responseFactory
        );

        $factory->createInstance(NullMiddleware::class);
        $this->assertTrue(true);
    }

    public function testCanCreateNullMiddleware()
    {
        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $factory = new MiddlewareChainItemFactory(
            $serverRequestFactory,
            $responseFactory
        );

        $instance = $factory->createNullChainItem();
        $this->assertInstanceOf(NullMiddleware::class, $instance);
    }

    public function testWillThrowExceptionOnNonExistentClassName()
    {
        /** @var MockInterface|ServerRequestFactoryInterface $serverRequestFactory */
        $serverRequestFactory = Mockery::mock(ServerRequestFactoryInterface::class);

        /** @var MockInterface|ResponseFactoryInterface $responseFactory */
        $responseFactory = Mockery::mock(ResponseFactoryInterface::class);

        $factory = new MiddlewareChainItemFactory(
            $serverRequestFactory,
            $responseFactory
        );

        $className = 'NonExistentClass';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class: [{$className}] does not exist");
        $factory->createInstance($className);
    }
}