<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy;

use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ResolvedMiddlewareChain;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\ChainNotFoundException;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Exception\InvalidAccessKeyException;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;
use RuntimeException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class RouteNameResolver extends AbstractChainResolver
{
    private const WILDCARD = '*';

    /** @var MiddlewareCollection[] */
    private array           $registeredRouteMiddlewares = [];
    private RouteCollection $routeCollection;

    public function __construct(
        RouterInterface $router
    ) {
        $this->routeCollection = $router->getRouteCollection();
    }

    public function registerRouteMiddleware(string $routeName, MiddlewareCollection $middlewareChain): self
    {
        if ($routeName === static::WILDCARD) {
            if (!empty($this->registeredRouteMiddlewares)) {
                return $this;
            }

            $this->registeredRouteMiddlewares[static::WILDCARD] = $middlewareChain;

            return $this;
        }

        if (array_key_exists(static::WILDCARD, $this->registeredRouteMiddlewares)) {
            return $this;
        }

        if (array_key_exists($routeName, $this->registeredRouteMiddlewares)) {
            return $this;
        }

        if ($this->routeCollection->get($routeName) === null) {
            throw new RuntimeException("Route with name: [{$routeName}] is not registered");
        }

        $this->registeredRouteMiddlewares[$routeName] = $middlewareChain;

        return $this;
    }

    public function handle(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain
    {
        if (isset($this->registeredRouteMiddlewares[static::WILDCARD])) {
            return ResolvedMiddlewareChain::createFromResolverContext(
                $this->registeredRouteMiddlewares[static::WILDCARD],
                ResolvedMiddlewareAccessKey::createFromMiddlewareResolver(
                    $this,
                    [
                        static::WILDCARD
                    ]
                )
            );
        }

        $routeName = $request->getRouteName();
        if (isset($this->registeredRouteMiddlewares[$routeName])) {
            return ResolvedMiddlewareChain::createFromResolverContext(
                $this->registeredRouteMiddlewares[$routeName],
                ResolvedMiddlewareAccessKey::createFromMiddlewareResolver(
                    $this,
                    [
                        $routeName
                    ]
                )
            );
        }

        return $this->handleNext($request);
    }


    /**
     * @inheritDoc
     */
    public function getChain(ResolvedMiddlewareAccessKey $accessKey): MiddlewareCollection
    {
        if (!$accessKey->isSameResolver($this)) {
            return $this->getChainNext($accessKey);
        }

        $class = static::class;
        $keys  = $accessKey->listPathParts();
        if (sizeof($keys) !== 1) {
            $implodedKey = implode(', ', $keys);

            throw new InvalidAccessKeyException("Bad access keys: [{$implodedKey}] in resolver: [{$class}]");
        }

        $key = current($keys);
        if (!array_key_exists($key, $this->registeredRouteMiddlewares)) {
            throw new ChainNotFoundException("Chain with key: [{$key}] was not found in resolver: [{$class}]");
        }

        return $this->registeredRouteMiddlewares[$key];
    }


    /**
     * @return ExportedMiddleware[]
     */
    public function exportRules(): array
    {
        $middlewareArray = [];
        foreach ($this->registeredRouteMiddlewares as $routeName => $middlewareChain) {
            $route = $this->routeCollection->get($routeName);
            if ($route === null) {
                throw new RuntimeException("Route: [{$routeName}] is not registered");
            }

            $httpMethods       = $route->getMethods();
            $compiledPath      = $route->compile()->getStaticPrefix();
            $middlewareArray[] = new ExportedMiddleware(
                $middlewareChain,
                CompoundHttpMethod::createFromStrings($httpMethods),
                $compiledPath,
                $routeName
            );
        }

        return $middlewareArray;
    }
}
