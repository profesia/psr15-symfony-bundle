<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Psr15Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('psr15');

        $rootNode = $builder->getRootNode();
        $rootNode->
            children()
                ->arrayNode('middleware_chains')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('middleware_chain_items')
                                ->isRequired()
                                ->scalarPrototype()->end()
                                ->validate()
                                    ->ifEmpty()
                                    ->thenInvalid('You need to added at least one middleware chain item class')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('routing')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('middleware_chain')
                            ->end()
                            ->arrayNode('condition')
                                ->children()
                                    ->scalarNode('uri_pattern')
                                        ->cannotBeEmpty()
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