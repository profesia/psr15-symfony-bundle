<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Middleware;

use RuntimeException;
use Profesia\Symfony\Psr15Bundle\RequestHandler\MiddlewareChainHandler;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareCollection
{
    /** @var MiddlewareInterface[] */
    private array $middlewares;

    public function __construct(array $middlewares)
    {
        if ($middlewares === []) {
            throw new RuntimeException('It is redundant to create MiddlewareChainHandler from empty array of middlewares');
        }

        $this->middlewares = $middlewares;
    }

    public function append(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function prepend(MiddlewareInterface $middleware): self
    {
        array_unshift($this->middlewares, $middleware);

        return $this;
    }

    public function transformToMiddlewareChainHandler(RequestHandlerInterface $requestHandler): MiddlewareChainHandler
    {
        return MiddlewareChainHandler::createFromFinalHandlerAndMiddlewares(
            $requestHandler,
            $this->middlewares
        );
    }

    public function isNullMiddleware(): bool
    {
        return ($this->middlewares[0] instanceof NullMiddleware);
    }

    /**
     * @return string[]
     */
    public function listClassNames(): array
    {
        return array_map(function (MiddlewareInterface $middleware) {
            return get_class($middleware);
        }, $this->middlewares);
    }
}
