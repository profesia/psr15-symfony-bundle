<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\Resolver\Strategy;

use Delvesoft\Psr15\Middleware\AbstractMiddlewareChainItem;
use Profesia\Symfony\Psr15Bundle\Middleware\Factory\MiddlewareChainItemFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Request\MiddlewareResolvingRequest;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\Dto\ExportedMiddleware;
use Profesia\Symfony\Psr15Bundle\ValueObject\CompoundHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use RuntimeException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CompiledPathStrategyResolver extends AbstractChainResolverItem
{
    /** @var array<int, array> */
    private array           $registeredPathMiddlewares = [];
    private RouteCollection $routeCollection;

    public function __construct(MiddlewareChainItemFactory $middlewareChainItemFactory, RouterInterface $router)
    {
        parent::__construct($middlewareChainItemFactory);
        $this->routeCollection = $router->getRouteCollection();
    }

    /**
     * @param ConfigurationPath                          $path
     * @param array<string, AbstractMiddlewareChainItem> $configuredMiddlewareChains
     *
     * @return $this
     */
    public function registerPathMiddleware(ConfigurationPath $path, array $configuredMiddlewareChains): self
    {
        $exportedConfiguration = $path->exportConfigurationForMiddleware($configuredMiddlewareChains);
        foreach ($exportedConfiguration as $pathLength => $registeredPatterns) {
            if (!isset($this->registeredPathMiddlewares[$pathLength])) {
                $this->registeredPathMiddlewares[$pathLength] = [];
            }

            foreach ($registeredPatterns as $pattern => $pathConfiguration) {
                if (!isset($this->registeredPathMiddlewares[$pathLength][$pattern])) {
                    $this->registeredPathMiddlewares[$pathLength][$pattern] = [];
                }

                foreach ($pathConfiguration as $method => $middlewareChain) {
                    if (isset($this->registeredPathMiddlewares[$pathLength][$pattern][$method])) {
                        $this->registeredPathMiddlewares[$pathLength][$pattern][$method]->append($configuredMiddlewareChains[$method]);
                    } else {
                        $this->registeredPathMiddlewares[$pathLength][$pattern][$method] = $configuredMiddlewareChains[$method];
                    }
                }
            }
        }

        return $this;
    }

    public function handle(MiddlewareResolvingRequest $request): AbstractMiddlewareChainItem
    {
        $route = $this->routeCollection->get($request->getRouteName());
        if ($route === null) {
            throw new RuntimeException("Route: [{$request->getRouteName()}] is not registered");
        }

        $staticPrefix =
            $route
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
        $groupedExport   = [];
        $middlewareArray = [];
        foreach ($this->registeredPathMiddlewares as $patternLength => $patterns) {
            foreach ($patterns as $pattern => $httpMethods) {
                if (!isset($groupedExport[$pattern])) {
                    $groupedExport[$pattern] = [];
                }


                /** @var AbstractMiddlewareChainItem $middlewareChain */
                foreach ($httpMethods as $httpMethod => $middlewareChain) {
                    $middlewareChainClassNames = $middlewareChain->listChainClassNames();
                    $middlewareListString      = implode('|', $middlewareChainClassNames);
                    if (!isset($groupedExport[$pattern][$middlewareListString])) {
                        $groupedExport[$pattern][$middlewareListString] = [
                            'chain'   => null,
                            'methods' => [],
                        ];
                    }

                    $groupedExport[$pattern][$middlewareListString]['chain']     = $middlewareChain;
                    $groupedExport[$pattern][$middlewareListString]['methods'][] = $httpMethod;
                }
            }
        }


        foreach ($groupedExport as $pattern => $item) {
            foreach ($item as $middlewareListString => $middlewareList) {
                $middlewareArray[] = new ExportedMiddleware(
                    $middlewareList['chain'],
                    CompoundHttpMethod::createFromStrings(
                        $middlewareList['methods']
                    ),
                    $pattern
                );
            }
        }

        return $middlewareArray;
    }
}