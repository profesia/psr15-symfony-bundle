<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Symfony\Component\HttpFoundation\Request;

interface RequestMiddlewareResolverInterface
{
    public function resolveMiddlewareChain(Request $request): AbstractMiddlewareChainItem;
}