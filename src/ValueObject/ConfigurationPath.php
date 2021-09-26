<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\ValueObject;

use InvalidArgumentException;
use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;

class ConfigurationPath
{
    private string                   $path;
    private ConfigurationHttpMethod  $method;

    private function __construct(ConfigurationHttpMethod $method, string $path)
    {
        $this->method = $method;
        $this->path   = $path;
    }

    public static function createFromConfigurationHttpMethodAndString(ConfigurationHttpMethod $httpMethod, string $path): self
    {
        $pathLength = strlen($path);
        if ($pathLength < 1 || strpos($path, '/') !== 0) {
            throw new InvalidArgumentException("Path should be a string composed at least of the '/' character");
        }

        return new self(
            $httpMethod,
            $path
        );
    }

    /**
     * @param array<string, MiddlewareCollection> $middlewareChains
     *
     * @return array<int, array<string, array<string, MiddlewareCollection>>>
     */
    public function exportConfigurationForMiddleware(array $middlewareChains): array
    {
        return [
            strlen($this->path) => [
                $this->path => $this->method->assignMiddlewareChainToHttpMethods($middlewareChains)
            ]
        ];
    }
}
