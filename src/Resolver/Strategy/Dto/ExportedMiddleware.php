<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class ExportedMiddleware
{
    private AbstractMiddlewareChainItem $middlewareChain;
    private ?string                     $routeName;
    private CompoundHttpMethod          $httpMethods;
    private string                      $path;

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