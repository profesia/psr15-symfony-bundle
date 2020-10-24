<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle;

use DeepCopy\DeepCopy;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Compiler\MiddlewareChainFactoryPass;
use Profesia\Symfony\Psr15Bundle\DependencyInjection\Psr15Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ProfesiaPsr15Bundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(
            new MiddlewareChainFactoryPass(
                new DeepCopy()
            )
        );
    }


    protected function getContainerExtensionClass(): string
    {
        return Psr15Extension::class;
    }
}