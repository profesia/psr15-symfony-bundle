<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Symfony\Component\HttpFoundation\Request;

class HttpRequestMiddlewareResolver
{
    /** @var AbstractMiddlewareChainItem[] */
    private $registeredUriPatterns = [];

    public function registerUriPatternMiddlewareChain(string $uriPattern, AbstractMiddlewareChainItem $chain): self
    {
        $this->registeredUriPatterns[$uriPattern] = $chain;

        return $this;
    }

    public function resolveMiddlewareChain(Request $request): ?AbstractMiddlewareChainItem
    {
        return $this->registeredUriPatterns[$request->getPathInfo()] ?? null;
    }
}