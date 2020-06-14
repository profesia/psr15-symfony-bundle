<?php

declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Psr15Extension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(
                __DIR__ . '/../Resources/config'
            )
        );

        $loader->load('services.yaml');

        $config = $this->processConfiguration(
            new Psr15Configuration(),
            $configs
        );

        $container->setParameter('psr15', $config);
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Psr15Configuration();
    }
}