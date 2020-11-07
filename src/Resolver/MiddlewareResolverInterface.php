<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver;

use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;

interface MiddlewareResolverInterface
{
    public function resolveMiddlewareChain(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain;
}