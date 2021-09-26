<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler;

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
     * @return array<string, Definition[]>
     */
    public function resolve(array $definitions): array
    {
        $middlewareChains = [];
        foreach ($definitions as $groupName => $groupConfig) {
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

                $middlewareChains[$groupName][] = $middlewareDefinition;
            }
        }

        return $middlewareChains;
    }
}
