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

                $middlewareDefinition = $this->container->getDefinition($middlewareAlias);
                if ($middlewareDefinition->getMethodCalls() !== []) {
                    throw new RuntimeException(
                        "Middleware with service alias: [{$middlewareAlias}] could not be included in chain. Only simple services (without additional calls) could be included"
                    );
                }

                $middlewareChain[] = $middlewareDefinition;
                //$middlewareChain[] = $this->container->getDefinition($middlewareAlias);
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

            /*$originalDefinition = $this->container->getDefinition($middlewareAlias);
            $methodCalls        = $originalDefinition->getMethodCalls();
            if ($methodCalls !== []) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware to prepend must not be a middleware chain"
                );
            }

            $middlewaresToPrepend[] = $originalDefinition;*/
            $middlewaresToPrepend[] = $this->container->getDefinition($middlewareAlias);
        }

        /** @var Definition $middleware */
        foreach (array_reverse($middlewaresToPrepend) as $middleware) {
            $middlewareCollection->addMethodCall('prepend', [$middleware]);
        }

        return $middlewareCollection;
    }

    /**
     * @param Definition       $middlewareCollection
     * @param array            $appendConfig
     * @param string           $conditionName
     *
     * @return Definition
     */
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

            /*$originalDefinition = $this->container->getDefinition($middlewareAlias);
            $methodCalls        = $originalDefinition->getMethodCalls();
            if ($methodCalls !== []) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware to append must not be a middleware chain"
                );
            }

            $middlewareCollection->addMethodCall('append', [$originalDefinition]);*/
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
