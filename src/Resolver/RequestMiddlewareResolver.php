<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolverItem;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Symfony\Component\HttpFoundation\Request;

class RequestMiddlewareResolver implements RequestMiddlewareResolverInterface
{
    private AbstractChainResolverItem $middlewareResolverChain;

    public function __construct(AbstractChainResolverItem $middlewareResolverChain)
    {
        $this->middlewareResolverChain = $middlewareResolverChain;
    }

    public function resolveMiddlewareChain(Request $request): AbstractMiddlewareChainItem
    {
        $routeName = $request->attributes->get('_route');
        if ($request->attributes->has('_locale')) {
            $routeName = "{$routeName}.{$request->attributes->get('_locale')}";
        }

        return $this->middlewareResolverChain->handle(
            new MiddlewareResolvingRequest(
                HttpMethod::createFromString(
                    $request->getRealMethod()
                ),
                $routeName
            )
        );
    }
}