<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Middleware;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NullMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}
