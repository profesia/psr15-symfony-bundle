<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\Dto;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class ExportedMiddleware
{
    /** @var AbstractMiddlewareChainItem */
    private $middlewareChain;

    /** @var string|null */
    private $routeName;

    /** @var HttpMethod */
    private $httpMethod;

    /** @var string */
    private $path;

    public function __construct(AbstractMiddlewareChainItem $middlewareChain, HttpMethod $httpMethod, string $path, ?string $routeName = null)
    {
        $this->middlewareChain = $middlewareChain;
        $this->httpMethod      = $httpMethod;
        $this->routeName       = $routeName;
        $this->path            = $path;
    }

    /**
     * @return string[]
     */
    public function listMiddlewareChainItems(): array
    {
        return $this->middlewareChain->listChainClassNames();
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getHttpMethod(): HttpMethod
    {
        return $this->httpMethod;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}