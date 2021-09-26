<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Middleware\NullMiddleware;
use Profesia\Symfony\Psr15Bundle\RequestHandler\MiddlewareChainHandler;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ResolvedMiddlewareChain implements MiddlewareInterface
{
    private MiddlewareCollection $middlewareChain;
    private ?ResolvedMiddlewareAccessKey $middlewareAccessKey;

    private function __construct(MiddlewareCollection $middlewareChain, ?ResolvedMiddlewareAccessKey $middlewareAccessKey = null)
    {
        $this->middlewareChain     = $middlewareChain;
        $this->middlewareAccessKey = $middlewareAccessKey;
    }

    public static function createFromResolverContext(
        MiddlewareCollection $middlewareChain,
        ResolvedMiddlewareAccessKey $middlewareAccessKey
    ): ResolvedMiddlewareChain {
        return new self(
            $middlewareChain,
            $middlewareAccessKey
        );
    }

    public static function createDefault(
        MiddlewareCollection $middlewareChain
    ): ResolvedMiddlewareChain {
        return new self(
            $middlewareChain
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middlewareChain
            ->transformToMiddlewareChainHandler($handler)
            ->handle($request);
    }

    public function isNullMiddleware(): bool
    {
        return $this->middlewareChain->isNullMiddleware();
    }

    public function getMiddlewareAccessKey(): ?ResolvedMiddlewareAccessKey
    {
        return $this->middlewareAccessKey;
    }

    public function listChainClassNames(): array
    {
        return $this->middlewareChain->listClassNames();
    }
}
