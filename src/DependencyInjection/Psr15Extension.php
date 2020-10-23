<?php

declare(strict_types=1);

namespace Profesia\Symfony\Psr15Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class Psr15Extension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        if (current($configs) === []) {
            //cache:clear should not fail during installation
            return;
        }

        $loader = new XmlFileLoader(
            $container,
            new FileLocator(
                __DIR__ . '/../Resources/config'
            )
        );

        $loader->load('services.xml');

        $config = $this->processConfiguration(
            new Psr15Configuration(),
            $configs
        );

        $container->setParameter('psr15', $config);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Psr15Configuration();
    }
}