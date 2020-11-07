<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;

class ResolvedMiddlewareChain
{
    private AbstractMiddlewareChainItem  $middlewareChain;
    private ?ResolvedMiddlewareAccessKey $middlewareAccessKey;

    private function __construct(AbstractMiddlewareChainItem $middlewareChain, ?ResolvedMiddlewareAccessKey $middlewareAccessKey = null)
    {
        $this->middlewareChain     = $middlewareChain;
        $this->middlewareAccessKey = $middlewareAccessKey;
    }

    public static function createFromResolverContext(
        AbstractMiddlewareChainItem $middlewareChain,
        ResolvedMiddlewareAccessKey $middlewareAccessKey
    ): ResolvedMiddlewareChain {
        return new self(
            $middlewareChain,
            $middlewareAccessKey
        );
    }

    public static function createDefault(
        AbstractMiddlewareChainItem $middlewareChain
    ): ResolvedMiddlewareChain {
        return new self(
            $middlewareChain
        );
    }

    public function getMiddlewareChain(): AbstractMiddlewareChainItem
    {
        return $this->middlewareChain;
    }

    public function isNullMiddleware(): bool
    {
        return ($this->middlewareChain instanceof NullMiddleware);
    }

    public function getMiddlewareAccessKey(): ?ResolvedMiddlewareAccessKey
    {
        return $this->middlewareAccessKey;
    }
}