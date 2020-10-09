<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Proxy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverCachingInterface;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RequestMiddlewareResolverCaching implements RequestMiddlewareResolverCachingInterface
{
    private RouteCollection                    $routeCollection;
    private RequestMiddlewareResolverInterface $resolver;
    private CacheItemPoolInterface             $cache;

    public function __construct(RouterInterface $router, RequestMiddlewareResolverInterface $resolver, CacheItemPoolInterface $cache)
    {
        $this->routeCollection = $router->getRouteCollection();
        $this->resolver        = $resolver;
        $this->cache           = $cache;
    }

    /**
     * @param Request $request
     *
     * @return AbstractMiddlewareChainItem
     * @throws InvalidArgumentException
     */
    public function resolveMiddlewareChain(Request $request): AbstractMiddlewareChainItem
    {
        $routeName = $request->attributes->get('_route');
        $route     = $this->routeCollection->get($routeName);
        if ($route === null) {
            throw new RuntimeException("Route: [{$routeName}] is not registered");
        }

        $staticPrefix = $route->compile()->getStaticPrefix();
        $cacheKey     = urlencode("{$request->getRealMethod()}-{$staticPrefix}");

        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $middleware = $this->resolver->resolveMiddlewareChain($request);
        $cacheItem->set($middleware);
        $this->cache->save($cacheItem);

        return $middleware;
    }
}