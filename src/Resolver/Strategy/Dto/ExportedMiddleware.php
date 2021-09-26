<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto;

use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class ExportedMiddleware
{
    private MiddlewareCollection $middlewareChain;
    private ?string                     $routeName;
    private CompoundHttpMethod          $httpMethods;
    private string                      $path;

    public function __construct(
        MiddlewareCollection $middlewareChain,
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
        return $this->middlewareChain->listClassNames();
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
