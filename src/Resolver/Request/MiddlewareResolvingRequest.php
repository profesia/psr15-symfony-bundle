<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Request;

use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;

final class MiddlewareResolvingRequest
{
    private HttpMethod                   $httpMethod;
    private CompiledRoute                $compiledRoute;
    private string                       $routeName;
    private string                       $cacheKey;
    private ?ResolvedMiddlewareAccessKey $accessKey;

    private function __construct(
        HttpMethod $httpMethod,
        CompiledRoute $compiledRoute,
        string $routeName,
        string $cacheKey,
        ?ResolvedMiddlewareAccessKey $accessKey = null
    ) {
        $this->httpMethod    = $httpMethod;
        $this->compiledRoute = $compiledRoute;
        $this->routeName     = $routeName;
        $this->cacheKey      = $cacheKey;
        $this->accessKey     = $accessKey;
    }

    public static function createFromFoundationAssets(Request $request, Route $route, string $routeName): MiddlewareResolvingRequest
    {
        $compiledRoute = $route->compile();
        $staticPrefix  = $compiledRoute->getStaticPrefix();
        $cacheKey      = urlencode("{$request->getRealMethod()}-{$staticPrefix}");

        return new static(
            HttpMethod::createFromString(
                $request->getRealMethod()
            ),
            $compiledRoute,
            $routeName,
            $cacheKey
        );
    }

    public function withResolvedMiddlewareAccessCode(ResolvedMiddlewareAccessKey $accessKey): MiddlewareResolvingRequest
    {
        return new static(
            $this->httpMethod,
            $this->compiledRoute,
            $this->routeName,
            $this->cacheKey,
            $accessKey
        );
    }

    public function hasAccessKey(): bool
    {
        return ($this->accessKey !== null);
    }

    public function getAccessKey(): ?ResolvedMiddlewareAccessKey
    {
        return $this->accessKey;
    }

    public function getHttpMethod(): HttpMethod
    {
        return $this->httpMethod;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getCompiledRoute(): CompiledRoute
    {
        return $this->compiledRoute;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }
}