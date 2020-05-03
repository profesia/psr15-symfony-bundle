<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\DependencyInjection\Compiler;

use Delvesoft\Symfony\Psr15Bundle\Resolver\HttpRequestMiddlewareResolver;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class MiddlewareChainFactoryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('psr15')) {
            return;
        }

        $namedRefs  = $this->findNamedServices($container);
        $parameters = $container->getParameter('psr15');
        ['middleware_chains' => $definitions, 'routing' => $routing] = $parameters;

        $middlewareChains = [];
        foreach ($definitions as $groupName => $groupConfig) {
            /** @var Definition $firstItemDefinition */
            $firstItemDefinition = null;
            foreach ($groupConfig['middleware_chain_items'] as $serviceAlias) {
                if (!isset($namedRefs[$serviceAlias])) {
                    throw new RuntimeException("Middleware with service alias: [{$serviceAlias}] is not registered as a service");
                }

                if ($firstItemDefinition === null) {
                    $definition          = $container->getDefinition($serviceAlias);
                    $firstItemDefinition = new Definition(
                        $definition->getClass(),
                        $definition->getArguments()
                    );

                    continue;
                }

                $firstItemDefinition->addMethodCall('append', [$namedRefs[$serviceAlias]]);
            }

            $middlewareChains[$groupName] = $firstItemDefinition;
        }

        $resolverDefinition = $container->getDefinition(HttpRequestMiddlewareResolver::class);
        foreach ($routing as $conditionName => $conditionConfig) {
            $condition = $conditionConfig['condition'];
            /*if (array_key_exists('uri_pattern', $condition) && array_key_exists('route_name', $condition)) {
                throw new RuntimeException(
                    "Error in condition: [{$conditionName}]. Condition configuration cannot have keys: [uri_pattern], [route_name] at the same time"
                );
            }*/

            $middlewareChainName = $conditionConfig['middleware_chain'];
            if (!isset($middlewareChains[$middlewareChainName])) {
                throw new RuntimeException(
                    "Error in condition: [{$conditionName}]. Middleware chain with name: [{$middlewareChainName}] does not exist"
                );
            }


            $resolverDefinition->addMethodCall(
                'registerUriPatternMiddlewareChain',
                [
                    $condition['uri_pattern'],
                    $middlewareChains[$middlewareChainName]
                ]
            );
        }
    }

    private function findNamedServices(ContainerBuilder $container)
    {
        $refs      = $this->findAndSortTaggedServices('psr15.middleware', $container);
        $namedRefs = [];
        foreach ($refs as $ref) {
            $namedRefs[(string)$ref] = $ref;
        }

        return $namedRefs;
    }


}