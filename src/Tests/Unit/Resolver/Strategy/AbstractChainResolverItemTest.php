<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Unit\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolverItem;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AbstractChainResolverItemTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCanHandleNoNextItem()
    {
        /** @var MockInterface|AbstractMiddlewareChainItem $nullMiddleware */
        $nullMiddleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|MiddlewareChainItemFactory $itemFactory */
        $itemFactory = Mockery::mock(MiddlewareChainItemFactory::class);
        $itemFactory
            ->shouldReceive('createNullChainItem')
            ->once()
            ->andReturn(
                $nullMiddleware
            );


        /** @var MockInterface|MiddlewareResolvingRequest $request */
        $request = Mockery::mock(MiddlewareResolvingRequest::class);

        $resolver1 = new DummyResolver(
            $itemFactory
        );

        $resolvedMiddleware = $resolver1->handle($request);
        $this->assertEquals($nullMiddleware, $resolvedMiddleware);
    }

    public function testCanHandleNextItem()
    {
        /** @var MockInterface|AbstractMiddlewareChainItem $middleware */
        $middleware = Mockery::mock(AbstractMiddlewareChainItem::class);

        /** @var MockInterface|MiddlewareChainItemFactory $itemFactory */
        $itemFactory = Mockery::mock(MiddlewareChainItemFactory::class);


        /** @var MockInterface|MiddlewareResolvingRequest $request */
        $request = Mockery::mock(MiddlewareResolvingRequest::class);

        $resolver1 = new DummyResolver(
            $itemFactory
        );

        /** @var MockInterface|AbstractChainResolverItem $resolver2 */
        $resolver2 = Mockery::mock(AbstractChainResolverItem::class);
        $resolver2
            ->shouldReceive('handle')
            ->once()
            ->withArgs(
                [
                    $request
                ]
            )->andReturn(
                $middleware
            );

        $resolver1->setNext($resolver2);
        $resolvedMiddleware = $resolver1->handle($request);
        $this->assertEquals($middleware, $resolvedMiddleware);
    }
}

class DummyResolver extends AbstractChainResolverItem
{
    public function handle(MiddlewareResolvingRequest $request): AbstractMiddlewareChainItem
    {
        return $this->handleNext($request);
    }

    public function exportRules(): array
    {
        return [];
    }
}