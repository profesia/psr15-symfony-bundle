<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Proxy;

use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverCachingInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class RequestMiddlewareResolverCaching implements RequestMiddlewareResolverCachingInterface
{
    private RequestMiddlewareResolverInterface $resolver;
    private CacheItemPoolInterface             $cache;

    public function __construct(RequestMiddlewareResolverInterface $resolver, CacheItemPoolInterface $cache)
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

        if ($cacheItem->isHit()) {
            $request = $request->withResolvedMiddlewareAccessCode(
                ResolvedMiddlewareAccessKey::createFromArray(
                    $cacheItem->get()
                )
            );
        }

        $resolvedMiddlewareChain = $this->resolver->resolveMiddlewareChain($request);
        if (!$resolvedMiddlewareChain->isNullMiddleware()) {
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