<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle;

use Delvesoft\Symfony\Psr15Bundle\DependencyInjection\Compiler\MiddlewareChainFactoryPass;
use Delvesoft\Symfony\Psr15Bundle\DependencyInjection\Psr15Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Psr15Bundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MiddlewareChainFactoryPass());
    }


    protected function getContainerExtensionClass(): string
    {
        return Psr15Extension::class;
    }
}