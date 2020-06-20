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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MiddlewareChainFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('psr15')) {
            return;
        }

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
        $middlewareChains = [];

        foreach ($definitions as $groupName => $groupConfig) {
            /** @var Definition $firstItemDefinition */
            $firstItemDefinition = null;

            foreach ($groupConfig['middleware_chain_items'] as $middlewareAlias) {
                if (!$container->hasDefinition($middlewareAlias)) {
                    throw new RuntimeException("Middleware with service alias: [{$middlewareAlias}] is not registered as a service");
                }

                $originalDefinition          = $container->getDefinition($middlewareAlias);
                $configurationPathDefinition = static::createDefinition(
                    $originalDefinition->getClass(),
                    false,
                    false,
                    $originalDefinition->getArguments()
                );

                if (!($firstItemDefinition instanceof Definition)) {
                    $firstItemDefinition = $configurationPathDefinition;

                    continue;
                }

                $firstItemDefinition->addMethodCall('append', [$configurationPathDefinition]);
            }

            $middlewareChains[$groupName] = $firstItemDefinition;
        }

        $routeNameStrategyResolver    = $container->getDefinition(RouteNameStrategyResolver::class);
        $compiledPathStrategyResolver = $container->getDefinition(CompiledPathStrategyResolver::class);
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

            $selectedMiddleware = null;
            if (!empty($conditionConfig['prepend'])) {
                foreach ($conditionConfig['prepend'] as $middlewareAlias) {
                    if (!$container->hasDefinition($middlewareAlias)) {
                        throw new RuntimeException(
                            "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                        );
                    }

                    $originalDefinition          = $container->getDefinition($middlewareAlias);
                    $configurationPathDefinition = static::createDefinition(
                        $originalDefinition->getClass(),
                        false,
                        false,
                        $originalDefinition->getArguments()
                    );


                    if ($selectedMiddleware === null) {
                        $selectedMiddleware = $configurationPathDefinition;

                        continue;
                    }

                    $selectedMiddleware->addMethodCall('append', [$configurationPathDefinition]);
                }

                $selectedMiddleware->addMethodCall('append', [$middlewareChains[$middlewareChainName]]);
            }

            if ($selectedMiddleware === null) {
                $selectedMiddleware = $middlewareChains[$middlewareChainName];
            }

            if (!empty($conditionConfig['append'])) {
                foreach ($conditionConfig['append'] as $middlewareAlias) {
                    if (!$container->hasDefinition($middlewareAlias)) {
                        throw new RuntimeException(
                            "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                        );
                    }

                    $originalDefinition = $container->getDefinition($middlewareAlias);
                    $selectedMiddleware->addMethodCall(
                        'append',
                        [
                            static::createDefinition(
                                $originalDefinition->getClass(),
                                false,
                                false,
                                $originalDefinition->getArguments()
                            )
                        ]
                    );
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

                    continue;
                }

                if ($containsPath) {
                    $hasMethod                         = array_key_exists('method', $condition);
                    $argumentsArray                    = $hasMethod ? [$condition['method']] : [];
                    $configurationHttpMethodDefinition = static::createDefinition(
                        ConfigurationHttpMethod::class,
                        false,
                        false,
                        $argumentsArray
                    );

                    if ($hasMethod) {
                        $configurationHttpMethodDefinition->setFactory(sprintf('%s::%s', ConfigurationHttpMethod::class, 'createFromString'));
                    } else {
                        $configurationHttpMethodDefinition->setFactory(sprintf('%s::%s', ConfigurationHttpMethod::class, 'createDefault'));
                    }

                    $configurationPathDefinition = static::createDefinition(
                        ConfigurationPath::class,
                        false,
                        false,
                        [$configurationHttpMethodDefinition, $condition['path']]
                    )->setFactory(
                        sprintf('%s::%s', ConfigurationPath::class, 'createFromConfigurationHttpMethodAndString')
                    );

                    $compiledPathStrategyResolver->addMethodCall(
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

    /**
     * @param string       $className
     * @param bool         $isShared
     * @param bool         $isPublic
     * @param array<mixed> $arguments
     *
     * @return Definition
     */
    private static function createDefinition(string $className, bool $isShared, bool $isPublic, array $arguments = []): Definition
    {
        return (new Definition($className, $arguments))
            ->setPublic($isPublic)
            ->setShared($isShared);
    }
}