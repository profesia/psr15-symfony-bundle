<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Decorator;

use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolverInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class MiddlewareResolverCaching implements MiddlewareResolverInterface
{
    private MiddlewareResolverInterface $resolver;
    private CacheItemPoolInterface $cache;

    public function __construct(MiddlewareResolverInterface $resolver, CacheItemPoolInterface $cache)
    {
        $this->resolver = $resolver;
        $this->cache    = $cache;
    }

    /**
     * @param MiddlewareResolvingRequest $request
     *
     * @return ResolvedMiddlewareChain
     * @throws InvalidArgumentException
     */
    public function resolveMiddlewareChain(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain
    {
        $cacheKey  = $request->getCacheKey();
        $cacheItem = $this->cache->getItem(
            $cacheKey
        );

        $isLoadedFromCache = $cacheItem->isHit();
        if ($isLoadedFromCache) {
            $request = $request->withResolvedMiddlewareAccessCode(
                ResolvedMiddlewareAccessKey::createFromArray(
                    $cacheItem->get()
                )
            );
        }

        $resolvedMiddlewareChain = $this->resolver->resolveMiddlewareChain($request);
        if ($resolvedMiddlewareChain->isNullMiddleware() || $isLoadedFromCache === true) {
            return $resolvedMiddlewareChain;
        }

        $cacheItem->set(
            $resolvedMiddlewareChain
                ->getMiddlewareAccessKey()
                ->toArray()
        );

        $this->cache->save($cacheItem);

        return $resolvedMiddlewareChain;
    }
}
