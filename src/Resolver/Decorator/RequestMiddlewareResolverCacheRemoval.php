<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Decorator;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverCachingInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestMiddlewareResolverCacheRemoval implements RequestMiddlewareResolverCachingInterface
{
    /** @var RequestMiddlewareResolverInterface */
    private $decoratedObject;

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(RequestMiddlewareResolverInterface $decoratedObject, CacheItemPoolInterface $cache)
    {
        $this->decoratedObject = $decoratedObject;
        $this->cache           = $cache;
    }

    public function resolveMiddlewareChain(Request $request): AbstractMiddlewareChainItem
    {
        $this->cache->clear();

        return $this->decoratedObject->resolveMiddlewareChain($request);
    }
}