<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\DependencyInjection;

use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Psr15Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('psr15');

        $rootNode = $builder->getRootNode();
        $rootNode->
            children()
                ->booleanNode('use_cache')
                    ->isRequired()
                ->end()
                ->arrayNode('middleware_chains')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->scalarPrototype()->end()
                        ->isRequired()
                        ->validate()
                            ->ifEmpty()
                            ->thenInvalid('You need to add at least one middleware chain item class')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('routing')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('middleware_chain')->end()
                            ->arrayNode('prepend')
                                ->scalarPrototype()->end()
                                    ->validate()
                                        ->ifEmpty()
                                        ->thenInvalid('You need to add at least one middleware chain item class to prepend')
                                ->end()
                            ->end()
                            ->arrayNode('append')
                                ->scalarPrototype()->end()
                                    ->validate()
                                        ->ifEmpty()
                                        ->thenInvalid('You need to add at least one middleware chain item class to append')
                                ->end()
                            ->end()
                            ->arrayNode('conditions')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('path')
                                        ->end()
                                        ->enumNode('method')
                                            ->values(ConfigurationHttpMethod::getPossibleValues())
                                        ->end()
                                        ->scalarNode('route_name')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}