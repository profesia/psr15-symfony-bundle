<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Middleware\Factory;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

class MiddlewareChainItemFactory
{
    public function createNullChainItem(): NullMiddleware
    {
        return new NullMiddleware();
    }
}
