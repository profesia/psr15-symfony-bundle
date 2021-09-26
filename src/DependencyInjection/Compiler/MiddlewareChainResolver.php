<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler;

use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MiddlewareChainResolver
{
    /**
     * @param ContainerBuilder $container
     * @param array            $definitions
     *
     * @return array<string, Definition[]>
     */
    public function resolve(ContainerBuilder $container, array $definitions): array
    {
        $middlewareChains = [];
        foreach ($definitions as $groupName => $groupConfig) {
            foreach ($groupConfig as $middlewareAlias) {
                if (!$container->hasDefinition($middlewareAlias)) {
                    throw new RuntimeException("Middleware with service alias: [{$middlewareAlias}] is not registered as a service");
                }

                $middlewareDefinition = $container->getDefinition($middlewareAlias);
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
