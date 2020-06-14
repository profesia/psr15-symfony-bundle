<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Proxy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Resolver\RequestMiddlewareResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\VarExporter\Instantiator;
use Symfony\Component\VarExporter\VarExporter;

class HttpRequestMiddlewareResolverProxy implements RequestMiddlewareResolverInterface
{
    /** @var RouteCollection */
    private $routeCollection;

    /** @var RequestMiddlewareResolverInterface */
    private $resolver;

    public function __construct(RouterInterface $router, RequestMiddlewareResolverInterface $resolver)
    {
        $this->routeCollection = $router->getRouteCollection();
        $this->resolver        = $resolver;
    }

    public function resolveMiddlewareChain(Request $request): AbstractMiddlewareChainItem
    {
        //@todo cache

        return $this->resolver->resolveMiddlewareChain($request);
    }
}