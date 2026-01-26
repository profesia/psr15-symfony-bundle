<?php

declare(strict_types=1);


namespace Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets;

use Nyholm\Psr7\Factory\Psr17Factory;
use Profesia\Symfony\Psr15Bundle\ProfesiaPsr15Bundle;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware1;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware2;
use Profesia\Symfony\Psr15Bundle\Tests\Integration\TestMiddleware3;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    private array $middlewaresConfig;

    public function __construct(array $middlewaresConfig)
    {
        $this->middlewaresConfig = $middlewaresConfig;

        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        return [
            new ProfesiaPsr15Bundle(),
            new FrameworkBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->services()->set(
            TestMiddleware1::class,
        );

        $container->services()->set(
            TestMiddleware2::class,
        );

        $container->services()->set(
            TestMiddleware3::class,
        );

        $container->services()->set(
            'nyholm.psr7.psr17_factory',
            Psr17Factory::class
        );

        $container->extension(
            'profesia_psr15',
            $this->middlewaresConfig
        );
    }


    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__ . '/../../../Resources/config/test-routes.php');
    }

    public function getCacheDir(): string
    {
        return __DIR__ . '/cache/' . spl_object_hash($this);
    }


}
