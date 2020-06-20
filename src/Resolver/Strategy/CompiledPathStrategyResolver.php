<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Delvesoft\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CompiledPathStrategyResolver extends AbstractChainResolverItem
{
    /** @var array */
    private $registeredPathMiddlewares = [];

    /** @var RouteCollection */
    private $routeCollection;

    public function __construct(MiddlewareChainItemFactory $middlewareChainItemFactory, RouterInterface $router)
    {
        parent::__construct($middlewareChainItemFactory);
        $this->routeCollection = $router->getRouteCollection();
    }

    public function registerPathMiddleware(ConfigurationPath $path, AbstractMiddlewareChainItem $middlewareChain): self
    {
        $exportedConfiguration = $path->exportConfigurationForMiddleware($middlewareChain);
        foreach ($exportedConfiguration as $pathLength => $registeredPaths) {
            if (!isset($this->registeredPathMiddlewares[$pathLength])) {
                $this->registeredPathMiddlewares[$pathLength] = [];
            }

            foreach ($registeredPaths as $path => $pathConfiguration) {
                if (!isset($this->registeredPathMiddlewares[$pathLength][$path])) {
                    $this->registeredPathMiddlewares[$pathLength][$path] = [];
                }

                foreach ($pathConfiguration as $method => $middlewareChain) {
                    if (isset($this->registeredPathMiddlewares[$pathLength][$path][$method])) {
                        continue;
                    }

                    $this->registeredPathMiddlewares[$pathLength][$path][$method] = $middlewareChain;
                }
            }
        }

        return $this;
    }

    public function handle(MiddlewareResolvingRequest $request): AbstractMiddlewareChainItem
    {
        $staticPrefix =
            $this->routeCollection
                ->get(
                    $request->getRouteName()
                )
                ->compile()
                ->getStaticPrefix();
        $pathLength   = strlen($staticPrefix);

        while ($pathLength >= 1) {
            if (!isset($this->registeredPathMiddlewares[$pathLength])) {
                $pathLength--;

                continue;
            }

            foreach ($this->registeredPathMiddlewares[$pathLength] as $path => $methodsConfig) {
                if (strpos($staticPrefix, $path) === 0) {
                    $extractedMiddleware = $request->getHttpMethod()->extractMiddleware($methodsConfig);

                    if ($extractedMiddleware !== null) {
                        return $extractedMiddleware;
                    }
                }
            }

            $pathLength--;
        }

        return $this->handleNext($request);
    }

    /**
     * @return ExportedMiddleware[]
     */
    public function exportRules(): array
    {
        $middlewareArray = [];
        foreach ($this->registeredPathMiddlewares as $patternLength => $patterns) {
            foreach ($patterns as $pattern => $httpMethods) {
                $middlewareArray[] = new ExportedMiddleware(
                    current($httpMethods),
                    CompoundHttpMethod::createFromStrings(
                        array_keys($httpMethods)
                    ),
                    $pattern
                );
            }
        }

        return $middlewareArray;
    }
}