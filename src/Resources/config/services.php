<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Http\Discovery\Psr17Factory;
use Profesia\Symfony\Psr15Bundle\Adapter\SymfonyControllerAdapter;
use Profesia\Symfony\Psr15Bundle\Console\Command\ListMiddlewareRulesCommand;
use Profesia\Symfony\Psr15Bundle\Console\Command\WarmUpMiddlewareCacheCommand;
use Profesia\Symfony\Psr15Bundle\Event\Subscriber\MiddlewareInjectionSubscriber;
use Profesia\Symfony\Psr15Bundle\RequestHandler\Factory\SymfonyControllerRequestHandlerFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\Decorator\MiddlewareResolverCacheRemoval;
use Profesia\Symfony\Psr15Bundle\Resolver\Decorator\MiddlewareResolverCaching;
use Profesia\Symfony\Psr15Bundle\Resolver\Factory\StrategyResolverFactory;
use Profesia\Symfony\Psr15Bundle\Resolver\MiddlewareResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\CompiledPathResolver;
use Profesia\Symfony\Psr15Bundle\Resolver\Strategy\RouteNameResolver;
use Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets\TestController;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();

    $services->set(HttpFoundationFactory::class);

    $services->set(Psr17Factory::class);

    $services->set(PsrHttpFactory::class)
        ->arg('$serverRequestFactory', service(Psr17Factory::class))
        ->arg('$streamFactory', service(Psr17Factory::class))
        ->arg('$uploadedFileFactory', service(Psr17Factory::class))
        ->arg('$responseFactory', service(Psr17Factory::class));

    $services->set(SymfonyControllerAdapter::class)
        ->arg('$httpMiddlewareResolver', service(MiddlewareResolver::class))
        ->arg('$foundationFactory', service(HttpFoundationFactory::class))
        ->arg('$psrRequestFactory', service(PsrHttpFactory::class))
        ->arg('$router', service('router.default'));

    $services->set(SymfonyControllerRequestHandlerFactory::class)
        ->arg('$foundationHttpFactory', service(HttpFoundationFactory::class))
        ->arg('$psrHttpFactory', service(PsrHttpFactory::class))
        ->arg('$requestStack', service('request_stack'));

    $services->set(MiddlewareInjectionSubscriber::class)
        ->tag('kernel.event_subscriber')
        ->arg('$symfonyControllerAdapter', service(SymfonyControllerAdapter::class));

    $services->set(RouteNameResolver::class)
        ->arg('$router', service('router.default'))
        ->share(false);

    $services->set(CompiledPathResolver::class)
        ->share(false);

    $services->set(StrategyResolverFactory::class);

    $services->set(ListMiddlewareRulesCommand::class)
        ->tag('console.command')
        ->arg('$routeNameStrategyResolver', service(RouteNameResolver::class))
        ->arg('$compiledPathStrategyResolver', service(CompiledPathResolver::class));

    $services->set(WarmUpMiddlewareCacheCommand::class)
        ->tag('console.command')
        ->args([
            service('router.default'),
            service('MiddlewareChainResolverCacheRemoval'),
        ]);

    $services->set('MiddlewareChainResolver', RouteNameResolver::class)
        ->factory([service(StrategyResolverFactory::class), 'create'])
        ->args([[
            service(RouteNameResolver::class),
            service(CompiledPathResolver::class),
        ]]);

    $services->set(MiddlewareResolver::class)
        ->arg('$middlewareResolverChain', service('MiddlewareChainResolver'))
        ->arg('$logger', service('logger')->nullOnInvalid())
        ->tag('monolog.logger', ['channel' => 'middleware']);

    $services->set('cache.psr15-middleware')
        ->parent('cache.system')
        ->private()
        ->tag('cache.pool');

    $services->set('MiddlewareChainResolverCaching', MiddlewareResolverCaching::class)
        ->arg('$resolver', service(MiddlewareResolver::class))
        ->arg('$cache', service('cache.psr15-middleware'));

    $services->set('MiddlewareChainResolverCacheRemoval', MiddlewareResolverCacheRemoval::class)
        ->arg('$decoratedObject', service('MiddlewareChainResolverCaching'))
        ->arg('$cache', service('cache.psr15-middleware'));

    $services->set(TestController::class)
        ->public()
        ->tag('container.service_subscriber');
};
