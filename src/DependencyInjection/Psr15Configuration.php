<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\DependencyInjection;

use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationHttpMethod;
use Delvesoft\Symfony\Psr15Bundle\ValueObject\ConfigurationPathMatchingStrategy;
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