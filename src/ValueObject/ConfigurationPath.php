<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\ValueObject;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use InvalidArgumentException;

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
     * @param AbstractMiddlewareChainItem $middlewareChain
     *
     * @return array<int, array>
     */
    public function exportConfigurationForMiddleware(AbstractMiddlewareChainItem $middlewareChain): array
    {
        return [
            strlen($this->path) => [
                $this->path => $this->method->assignMiddlewareChainToHttpMethods($middlewareChain)
            ]
        ];
    }
}