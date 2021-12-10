<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler;

use Profesia\Symfony\Psr15Bundle\Middleware\MiddlewareCollection;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MiddlewareChainResolver
{
    private ContainerBuilder $container;
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * @param array            $definitions
     *
     * @return Definition[]
     */
    public function resolve(array $definitions): array
    {
        $middlewareDefinitions = [];
        foreach ($definitions as $groupName => $groupConfig) {
            $middlewareChain = [];
            foreach ($groupConfig as $middlewareAlias) {
                if (!$this->container->hasDefinition($middlewareAlias)) {
                    throw new RuntimeException("Middleware with service alias: [{$middlewareAlias}] is not registered as a service");
                }

                $middlewareChain[] = $this->container->getDefinition($middlewareAlias);
            }


            $chainDefinition = new Definition(MiddlewareCollection::class, [$middlewareChain]);
            $chainDefinition
                ->setPublic(false)
                ->setShared(false);
            $middlewareDefinitions[$groupName] = $chainDefinition;
        }

        return $middlewareDefinitions;
    }

    /**
     * @param Definition       $middlewareCollection
     * @param array            $prependConfig
     * @param string           $conditionName
     *
     * @return Definition
     */
    public function resolveMiddlewaresToPrepend(
        Definition $middlewareCollection,
        array $prependConfig,
        string $conditionName
    ): Definition {
        if ($prependConfig === []) {
            return $middlewareCollection;
        }

        $middlewaresToPrepend = [];
        foreach ($prependConfig as $middlewareAlias) {
            if (!$this->container->hasDefinition($middlewareAlias)) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                );
            }

            $middlewaresToPrepend[] = $this->container->getDefinition($middlewareAlias);
        }

        /** @var Definition $middleware */
        foreach (array_reverse($middlewaresToPrepend) as $middleware) {
            $middlewareCollection->addMethodCall('prepend', [$middleware]);
        }

        return $middlewareCollection;
    }

    public function resolveMiddlewaresToAppend(
        Definition $middlewareCollection,
        array $appendConfig,
        string $conditionName
    ): Definition {
        if ($appendConfig === []) {
            return $middlewareCollection;
        }

        foreach ($appendConfig as $middlewareAlias) {
            if (!$this->container->hasDefinition($middlewareAlias)) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                );
            }

            $middlewareCollection->addMethodCall(
                'append',
                [
                    $this->container->getDefinition($middlewareAlias)
                ]
            );
        }

        return $middlewareCollection;
    }
}
