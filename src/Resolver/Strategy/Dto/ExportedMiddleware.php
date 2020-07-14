<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\Dto;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class ExportedMiddleware
{
    /** @var AbstractMiddlewareChainItem */
    private $middlewareChain;

    /** @var string|null */
    private $routeName;

    /** @var CompoundHttpMethod */
    private $httpMethods;

    /** @var string */
    private $path;

    public function __construct(
        AbstractMiddlewareChainItem $middlewareChain,
        CompoundHttpMethod $httpMethods,
        string $path,
        ?string $routeName = null
    ) {
        $this->middlewareChain = $middlewareChain;
        $this->httpMethods     = $httpMethods;
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

    public function getIdentifier(): string
    {
        return $this->routeName ?? $this->path;
    }

    public function getHttpMethods(): CompoundHttpMethod
    {
        if ($this->httpMethods->isEmpty()) {
            return CompoundHttpMethod::createFromStrings(
                HttpMethod::getPossibleValues()
            );
        }

        return $this->httpMethods;
    }
}