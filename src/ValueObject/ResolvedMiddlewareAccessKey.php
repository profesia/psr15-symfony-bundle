<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\ValueObject;

use InvalidArgumentException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\AbstractChainResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;

final class ResolvedMiddlewareAccessKey
{
    private const RESOLVERS      = [
        RouteNameResolver::class    => true,
        CompiledPathResolver::class => true
    ];
    private const ACCESS_PATH    = 'accessPath';
    private const RESOLVER_CLASS = 'resolverClass';

    private array  $accessPath;
    private string $resolverClass;

    private function __construct(array $accessPath, string $resolverClass)
    {
        $this->accessPath    = $accessPath;
        $this->resolverClass = $resolverClass;
    }

    public static function createFromArray(array $input): ResolvedMiddlewareAccessKey
    {
        $accessPathKey = self::ACCESS_PATH;
        if (!array_key_exists($accessPathKey, $input)) {
            throw new InvalidArgumentException("Key: [{$accessPathKey}] is not present in input argument");
        }


        $resolverKey = self::RESOLVER_CLASS;
        if (!array_key_exists($resolverKey, $input)) {
            throw new InvalidArgumentException("Key: [{$resolverKey}] is not present in input argument");
        }

        return self::create(
            $input[$resolverKey],
            $input[$accessPathKey]
        );
    }

    public static function createFromMiddlewareResolver(AbstractChainResolver $resolver, array $accessPath): ResolvedMiddlewareAccessKey
    {
        return self::create(
            get_class($resolver),
            $accessPath
        );
    }

    private static function create(string $resolverClass, array $accessPath): ResolvedMiddlewareAccessKey
    {
        if (!array_key_exists($resolverClass, self::RESOLVERS)) {
            throw new InvalidArgumentException("Resolver: [{$resolverClass}] is not supported");
        }

        return new self(
            $accessPath,
            $resolverClass
        );
    }

    public function toArray(): array
    {
        return [
            self::RESOLVER_CLASS => $this->resolverClass,
            self::ACCESS_PATH    => $this->accessPath,
        ];
    }

    /**
     * @return string[]
     */
    public function listPathParts(): array
    {
        return $this->accessPath;
    }

    public function isSameResolver(AbstractChainResolver $resolver): bool
    {
        return ($this->resolverClass === get_class($resolver));
    }
}
