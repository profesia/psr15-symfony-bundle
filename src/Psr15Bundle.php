<?php declare(strict_types=1);

namespace Delvesoft\Symfony\Psr15Bundle;

use Delvesoft\Symfony\Psr15Bundle\DependencyInjection\Psr15Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Psr15Bundle extends Bundle
{
    protected function getContainerExtensionClass(): ExtensionInterface
    {
        return new Psr15Extension();
    }
}