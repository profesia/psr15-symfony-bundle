<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\DependencyInjection\Compiler;

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
        if (!$container->hasParameter('psr15.middleware_groups')) {
            return;
        }

        $namedRefs  = $this->findNamedServices($container);
        $definition = $container->getParameter('psr15.middleware_groups');
        
        foreach ($definition as $groupName => $groupConfig) {

            /** @var Definition $firstItemDefinition */
            $firstItemDefinition = null;
            foreach ($groupConfig['middleware_chain_items'] as $class) {
                if (!isset($namedRefs[$class])) {
                    throw new RuntimeException("Middleware: [{$class}] is not registered as a service");
                }
                
                if ($firstItemDefinition === null) {
                    $firstItemDefinition = $container->getDefinition($class);

                    continue;
                }

                $firstItemDefinition->addMethodCall('append', [$namedRefs[$class]]);
            }
            
            $container->setDefinition("psr15.middleware_chain.{$groupName}", $firstItemDefinition);
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