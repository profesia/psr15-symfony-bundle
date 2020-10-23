<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Request;

use Profesia\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class MiddlewareResolvingRequest
{
    private string     $routeName;
    private HttpMethod $httpMethod;

    public function __construct(HttpMethod $httpMethod, string $routeName)
    {
        $this->httpMethod = $httpMethod;
        $this->routeName  = $routeName;
    }

    public function getHttpMethod(): HttpMethod
    {
        return $this->httpMethod;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }
}