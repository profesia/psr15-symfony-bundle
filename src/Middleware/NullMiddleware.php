<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Middleware;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class NullMiddleware extends AbstractMiddlewareChainItem
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}