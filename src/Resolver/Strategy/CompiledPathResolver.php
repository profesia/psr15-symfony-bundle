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
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use Profesia\Symfony\Psr15Bundle\ValueObject\ResolvedMiddlewareAccessKey;

class CompiledPathResolver extends AbstractChainResolver
{
    /** @var array<int, array<string, array<string, MiddlewareCollection>>> */
    private array $registeredPathMiddlewares = [];

    /**
     * @param ConfigurationPath                          $path
     * @param array<string, MiddlewareCollection> $configuredMiddlewareChains
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
                        $this->registeredPathMiddlewares[$pathLength][$pattern][$method]->appendCollection($configuredMiddlewareChains[$method]);
                    } else {
                        $this->registeredPathMiddlewares[$pathLength][$pattern][$method] = $configuredMiddlewareChains[$method];
                    }
                }
            }
        }

        return $this;
    }

    public function handle(MiddlewareResolvingRequest $request): ResolvedMiddlewareChain
    {
        $staticPrefix = $request->getCompiledRoute()->getStaticPrefix();
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
                        return ResolvedMiddlewareChain::createFromResolverContext(
                            $extractedMiddleware,
                            ResolvedMiddlewareAccessKey::createFromMiddlewareResolver(
                                $this,
                                [
                                    $pathLength,
                                    $path,
                                    $request->getHttpMethod()->toString()
                                ]
                            )
                        );
                    }
                }
            }

            $pathLength--;
        }

        return $this->handleNext($request);
    }

    /**
     * @inheritDoc
     */
    public function getChain(ResolvedMiddlewareAccessKey $accessKey): ResolvedMiddlewareChain
    {
        if (!$accessKey->isSameResolver($this)) {
            return $this->getChainNext($accessKey);
        }

        $keys  = $accessKey->listPathParts();
        $class = static::class;
        if (sizeof($keys) !== 3) {
            $implodedKey = implode(', ', $keys);

            throw new InvalidAccessKeyException("Bad access keys: [{$implodedKey}] in resolver: [{$class}]");
        }

        [$pathLength, $pattern, $httpMethod] = $keys;
        if (!isset($this->registeredPathMiddlewares[$pathLength])) {
            throw new ChainNotFoundException("Key: [{$pathLength}] was not found in resolver: [{$class}]");
        }

        if (!isset($this->registeredPathMiddlewares[$pathLength][$pattern])) {
            throw new ChainNotFoundException("Keys: [{$pathLength}, {$pattern}] was not found in resolver: [{$class}]");
        }

        if (!isset($this->registeredPathMiddlewares[$pathLength][$pattern][$httpMethod])) {
            throw new ChainNotFoundException("Chain with key: [{$pathLength}, {$pattern}, {$httpMethod}] was not found in resolver: [{$class}]");
        }

        return ResolvedMiddlewareChain::createFromResolverContext(
            $this->registeredPathMiddlewares[$pathLength][$pattern][$httpMethod],
            $accessKey
        );
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


                /** @var MiddlewareCollection $middlewareChain */
                foreach ($httpMethods as $httpMethod => $middlewareChain) {
                    $middlewareChainClassNames = $middlewareChain->listClassNames();
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
