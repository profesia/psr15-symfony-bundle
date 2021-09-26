<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\RequestHandler;

use RuntimeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareChainHandler implements RequestHandlerInterface
{
    private RequestHandlerInterface $requestHandler;
    private MiddlewareInterface $middleware;

    public function __construct(RequestHandlerInterface $requestHandler, MiddlewareInterface $middleware)
    {
        $this->requestHandler = $requestHandler;
        $this->middleware     = $middleware;
    }

    public static function createFromFinalHandlerAndMiddlewares(RequestHandlerInterface $requestHandler, array $middlewares): MiddlewareChainHandler
    {
        if ($middlewares === []) {
            throw new RuntimeException('It is redundant to create MiddlewareChainHandler from empty array of middlewares');
        }

        $lastHandler = null;
        foreach (array_reverse($middlewares) as $middleware) {
            $lastHandler = new MiddlewareChainHandler(
                ($lastHandler === null) ? $requestHandler : $lastHandler,
                $middleware
            );
        }

        return $lastHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process(
            $request,
            $this->requestHandler
        );
    }
}
