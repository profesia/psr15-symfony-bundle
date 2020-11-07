<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Decorator;

use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverCachingInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Psr\Cache\CacheItemPoolInterface;

class MiddlewareResolverCacheRemoval implements MiddlewareResolverCachingInterface
{
    private MiddlewareResolverInterface $decoratedObject;
    private CacheItemPoolInterface             $cache;

    public function __construct(MiddlewareResolverInterface $decoratedObject, CacheItemPoolInterface $cache)
    {
        $this->decoratedObject = $decoratedObject;
        $this->cache           = $cache;
    }

    public function resolveMiddlewareChain(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain
    {
        $this->cache->clear();

        return $this->decoratedObject->resolveMiddlewareChain($request);
    }
}