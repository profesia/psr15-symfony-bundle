<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Request;

use Delvesoft\Symfony\Psr15Bundle\ValueObject\HttpMethod;

class MiddlewareResolvingRequest
{
    /** @var string */
    private $routeName;

    /** @var HttpMethod */
    private $httpMethod;

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