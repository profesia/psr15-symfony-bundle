<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Proxy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

class HttpRequestMiddlewareResolverProxy implements RequestMiddlewareResolverInterface
{
    /** @var RouteCollection */
    private $routeCollection;

    /** @var RequestMiddlewareResolverInterface */
    private $resolver;

    /** @var CacheInterface */
    private $cache;

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
        $routeName    = $request->attributes->get('_route');
        $staticPrefix = $this->routeCollection->get($routeName)->compile()->getStaticPrefix();
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