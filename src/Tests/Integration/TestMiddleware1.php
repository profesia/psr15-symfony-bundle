<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Tests\Integration;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TestMiddleware1 extends AbstractMiddlewareChainItem
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->processNext(
            $request,
            $handler
        );
    }
}