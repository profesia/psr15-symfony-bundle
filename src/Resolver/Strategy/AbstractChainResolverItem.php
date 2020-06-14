<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;

abstract class AbstractChainResolverItem
{
    /** @var MiddlewareChainItemFactory */
    private $middlewareChainItemFactory;

    /** @var null|AbstractChainResolverItem */
    private $next;

    public function __construct(MiddlewareChainItemFactory $middlewareChainItemFactory)
    {
        $this->middlewareChainItemFactory = $middlewareChainItemFactory;
    }

    public function setNext(AbstractChainResolverItem $chainItem): self
    {
        $this->next = $chainItem;

        return $this;
    }

    public abstract function handle(MiddlewareResolvingRequest $request): AbstractMiddlewareChainItem;

    protected function handleNext(MiddlewareResolvingRequest $request): AbstractMiddlewareChainItem
    {
        if ($this->next === null) {
            return $this->middlewareChainItemFactory->createNullChainItem();
        }

        return $this->next->handle($request);
    }
}