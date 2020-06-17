<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\DependencyInjection\Compiler;

use Delvesoft\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Delvesoft\Symfony\Psr15Bundle\Console\Command\ListMiddlewareRulesCommand;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathStrategyResolver;
use Delvesoft\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameStrategyResolver;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MiddlewareChainFactoryPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('psr15')) {
            return;
        }

        $namedRefs         = $this->findNamedServices($container);
        $parameters        = $container->getParameter('psr15');
        $adapterDefinition = $container->getDefinition(SymfonyControllerAdapter::class);
        if ($parameters['use_cache'] === true) {
            $adapterDefinition->setArguments(
                [
                    new Reference('MiddlewareChainResolverProxy')
                ]
            );
        }

        ['middleware_chains' => $definitions, 'routing' => $routing] = $parameters;
        $middlewareChains    = [];
        $middlewareGroupInfo = [];

        foreach ($definitions as $groupName => $groupConfig) {
            /** @var Definition $firstItemDefinition */
            $firstItemDefinition             = null;
            $middlewareGroupInfo[$groupName] = [];

            foreach ($groupConfig['middleware_chain_items'] as $serviceAlias) {
                if (!isset($namedRefs[$serviceAlias])) {
                    throw new RuntimeException("Middleware with service alias: [{$serviceAlias}] is not registered as a service");
                }


                $configurationPathDefinition       = $container->getDefinition($serviceAlias);
                $middlewareGroupInfo[$groupName][] = $configurationPathDefinition->getClass();

                if ($firstItemDefinition === null) {
                    $firstItemDefinition = new Definition(
                        $configurationPathDefinition->getClass(),
                        $configurationPathDefinition->getArguments()
                    );


                    continue;
                }

                $firstItemDefinition->addMethodCall('append', [$namedRefs[$serviceAlias]]);
            }

            $middlewareChains[$groupName] = $firstItemDefinition;
        }

        $routeNameStrategyResolver    = $container->getDefinition(RouteNameStrategyResolver::class);
        $compiledPathStrategyResolver = $container->getDefinition(CompiledPathStrategyResolver::class);
        $listMiddlewareCommand        = $container->getDefinition(ListMiddlewareRulesCommand::class);
        foreach ($routing as $conditionName => $conditionConfig) {
            $middlewareChainName = $conditionConfig['middleware_chain'];
            if (!isset($middlewareChains[$middlewareChainName])) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware chain with name: [{$middlewareChainName}] does not exist"
                );
            }

            if ($conditionConfig['conditions'] === []) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. At least one condition has to be specified"
                );
            }

            $selectedMiddleware          = null;
            $selectedMiddlewareChainInfo = $middlewareGroupInfo[$middlewareChainName];
            if (!empty($conditionConfig['prepend'])) {
                $startIndex = 0;
                foreach ($conditionConfig['prepend'] as $middlewareAlias) {
                    if (!isset($namedRefs[$middlewareAlias])) {
                        throw new RuntimeException(
                            "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                        );
                    }

                    $configurationPathDefinition = $container->getDefinition($middlewareAlias);
                    array_splice($selectedMiddlewareChainInfo, $startIndex, 0, $configurationPathDefinition->getClass());
                    $startIndex++;

                    if ($selectedMiddleware === null) {
                        $selectedMiddleware = new Definition(
                            $configurationPathDefinition->getClass(),
                            $configurationPathDefinition->getArguments()
                        );

                        continue;
                    }

                    $selectedMiddleware->addMethodCall('append', [$namedRefs[$middlewareAlias]]);
                }

                $selectedMiddleware->addMethodCall('append', [$middlewareChains[$middlewareChainName]]);
            }

            if ($selectedMiddleware === null) {
                $selectedMiddleware = $middlewareChains[$middlewareChainName];
            }

            if (!empty($conditionConfig['append'])) {
                foreach ($conditionConfig['append'] as $middlewareAlias) {
                    if (!isset($namedRefs[$middlewareAlias])) {
                        throw new RuntimeException(
                            "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                        );
                    }

                    $configurationPathDefinition = $container->getDefinition($middlewareAlias);
                    array_push($selectedMiddlewareChainInfo, $configurationPathDefinition->getClass());

                    $middlewareReference = new Reference($configurationPathDefinition->getClass());

                    $selectedMiddleware->addMethodCall('append', [$middlewareReference]);
                }
            }

            foreach ($conditionConfig['conditions'] as $condition) {
                $containsPath      = array_key_exists('path', $condition);
                $containsRouteName = array_key_exists('route_name', $condition);

                if ($containsPath === $containsRouteName) {
                    throw new RuntimeException(
                        "Error in condition config: [{$conditionName}]. Condition config has to have either 'route_name' or 'path' config"
                    );
                }

                if ($containsRouteName) {
                    if (array_key_exists('method', $condition) || array_key_exists('strategy', $condition)) {
                        throw new RuntimeException(
                            "Error in condition config: [{$conditionName}]. Keys: 'method' and 'strategy' are redundant for condition with 'route_name'"
                        );
                    }

                    $routeNameStrategyResolver->addMethodCall(
                        'registerRouteMiddleware',
                        [
                            $condition['route_name'],
                            $selectedMiddleware
                        ]
                    );

                    $listMiddlewareCommand->addMethodCall(
                        'registerRouteMiddleware',
                        [
                            $condition['route_name'],
                            $selectedMiddlewareChainInfo,
                            $middlewareChainName
                        ]
                    );

                    continue;
                }

                if ($containsPath) {
                    $hasMethod                         = array_key_exists('method', $condition);
                    $argumentsArray                    = $hasMethod ? [$condition['method']] : [];
                    $configurationHttpMethodDefinition = (new Definition(ConfigurationHttpMethod::class, $argumentsArray))
                        ->setShared(false)
                        ->setPublic(false);
                    if ($hasMethod) {
                        $configurationHttpMethodDefinition->setFactory(sprintf('%s::%s', ConfigurationHttpMethod::class, 'createFromString'));
                    } else {
                        $configurationHttpMethodDefinition->setFactory(sprintf('%s::%s', ConfigurationHttpMethod::class, 'createDefault'));
                    }

                    $configurationPathDefinition =
                        (new Definition(ConfigurationPath::class, [$configurationHttpMethodDefinition, $condition['path']]))
                            ->setShared(false)
                            ->setPublic(false)
                            ->setFactory(sprintf('%s::%s', ConfigurationPath::class, 'createFromConfigurationHttpMethodAndString'));

                    $compiledPathStrategyResolver->addMethodCall(
                        'registerPathMiddleware',
                        [
                            $configurationPathDefinition,
                            $selectedMiddleware
                        ]
                    );

                    $listMiddlewareCommand->addMethodCall(
                        'registerPathMiddleware',
                        [
                            $configurationPathDefinition,
                            $selectedMiddleware
                        ]
                    );
                }
            }
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