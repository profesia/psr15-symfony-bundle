<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler;

use DeepCopy\DeepCopy;
use Profesia\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Profesia\Symfony\Psr15Bundle\ValueObject\ConfigurationPath;
use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MiddlewareChainFactoryPass implements CompilerPassInterface
{
    private MiddlewareChainResolver $chainResolver;
    private DeepCopy $cloner;

    public function __construct(MiddlewareChainResolver $chainResolver, DeepCopy $cloner)
    {
        $this->chainResolver = $chainResolver;
        $this->cloner        = $cloner;
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('profesia_psr15')) {
            return;
        }

        $parameters = (array)$container->getParameter('profesia_psr15');
        $this->turnOnCaching(
            $container->getDefinition(SymfonyControllerAdapter::class),
            $parameters['use_cache']
        );

        ['middleware_chains' => $definitions, 'routing' => $routing] = $parameters;
        $middlewareChains = $this->chainResolver->resolve($definitions);

        $routeNameStrategyResolver    = $container->getDefinition(RouteNameResolver::class);
        $compiledPathStrategyResolver = $container->getDefinition(CompiledPathResolver::class);
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

            $selectedMiddlewareChain = $this->resolveMiddlewaresToPrepend(
                $container,
                $middlewareChains[$middlewareChainName],
                $conditionConfig['prepend'] ?? [],
                $conditionName
            );

            $selectedMiddlewareChain = $this->resolveMiddlewareChainToAppend(
                $container,
                $selectedMiddlewareChain,
                $conditionConfig['append'] ?? [],
                $conditionName
            );

            foreach ($conditionConfig['conditions'] as $condition) {
                $containsPath      = array_key_exists('path', $condition);
                $containsRouteName = array_key_exists('route_name', $condition);

                if ($containsPath === $containsRouteName) {
                    throw new RuntimeException(
                        "Error in condition config: [{$conditionName}]. Condition config has to have either 'route_name' or 'path' config"
                    );
                }

                if ($containsRouteName) {
                    if (array_key_exists('method', $condition)) {
                        throw new RuntimeException(
                            "Error in condition config: [{$conditionName}]. Key: 'method' is redundant for condition with 'route_name'"
                        );
                    }

                    $routeNameStrategyResolver->addMethodCall(
                        'registerRouteMiddleware',
                        [
                            $condition['route_name'],
                            $selectedMiddlewareChain,
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


                    $splitHttpMethods = ($hasMethod === true)
                        ?
                        ConfigurationHttpMethod::validateAndSplit(
                            $condition['method']
                        ) : ConfigurationHttpMethod::getPossibleValues();

                    $selectedMiddlewareArray = [];
                    foreach ($splitHttpMethods as $httpMethod) {
                        $selectedMiddlewareArray[$httpMethod] = $selectedMiddlewareChain;
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
                            $selectedMiddlewareArray,
                        ]
                    );
                }
            }
        }
    }

    public function turnOnCaching(Definition $symfonyControllerAdapter, bool $useCase): void
    {
        if ($useCase === true) {
            $symfonyControllerAdapter->replaceArgument(
                '$httpMiddlewareResolver',
                new Reference('MiddlewareChainResolverCaching')
            );
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $middlewareCollection
     * @param array            $prependConfig
     * @param string           $conditionName
     *
     * @return Definition
     */
    public function resolveMiddlewaresToPrepend(
        ContainerBuilder $container,
        Definition $middlewareCollection,
        array $prependConfig,
        string $conditionName
    ): Definition {
        if ($prependConfig === []) {
            return $middlewareCollection;
        }

        $middlewaresToPrepend = [];
        foreach ($prependConfig as $middlewareAlias) {
            if (!$container->hasDefinition($middlewareAlias)) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                );
            }

            $originalDefinition = $container->getDefinition($middlewareAlias);
            $methodCalls        = $originalDefinition->getMethodCalls();
            if ($methodCalls !== []) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware to prepend must not be a middleware chain"
                );
            }

            $middlewaresToPrepend[] = $originalDefinition;
        }

        /** @var Definition $middleware */
        foreach (array_reverse($middlewaresToPrepend) as $middleware) {
            $middlewareCollection->addMethodCall('prepend', [$middleware]);
        }

        return $middlewareCollection;
    }

    /**
     * @param ContainerBuilder $container
     * @param Definition       $middlewareCollection
     * @param array            $appendConfig
     * @param string           $conditionName
     *
     * @return Definition
     */
    public function resolveMiddlewareChainToAppend(
        ContainerBuilder $container,
        Definition $middlewareCollection,
        array $appendConfig,
        string $conditionName
    ): Definition {
        if ($appendConfig === []) {
            return $middlewareCollection;
        }

        foreach ($appendConfig as $middlewareAlias) {
            if (!$container->hasDefinition($middlewareAlias)) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware with service alias: [{$middlewareAlias}] is not registered as a service"
                );
            }

            $originalDefinition = $container->getDefinition($middlewareAlias);
            $methodCalls        = $originalDefinition->getMethodCalls();
            if ($methodCalls !== []) {
                throw new RuntimeException(
                    "Error in condition config: [{$conditionName}]. Middleware to append must not be a middleware chain"
                );
            }

            $middlewareCollection->addMethodCall('append', [$originalDefinition]);
        }

        return $middlewareCollection;
    }

    private function copyDefinition(Definition $originalDefinition): Definition
    {
        /** @var Definition $newDefinition */
        $newDefinition = $this->cloner->copy($originalDefinition);
        $newDefinition
            ->setPublic(false)
            ->setShared(false);

        return $newDefinition;
    }

    /**
     * @param string  $className
     * @param bool    $isShared
     * @param bool    $isPublic
     * @param mixed[] $arguments
     *
     * @return Definition
     */
    private static function createDefinition(string $className, bool $isShared, bool $isPublic, array $arguments = []): Definition
    {
        $definition = new Definition($className, $arguments);
        $definition
            ->setPublic($isPublic)
            ->setShared($isShared);

        return $definition;
    }
}
