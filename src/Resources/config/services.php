<?php

declare(strict_types=1);

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
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(HttpFoundationFactory::class)
        ->autowire(true)
        ->autoconfigure(true);

    $services->set(PsrHttpFactory::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$serverRequestFactory', service('nyholm.psr7.psr17_factory'))
        ->arg('$streamFactory', service('nyholm.psr7.psr17_factory'))
        ->arg('$uploadedFileFactory', service('nyholm.psr7.psr17_factory'))
        ->arg('$responseFactory', service('nyholm.psr7.psr17_factory'));

    $services->set(SymfonyControllerAdapter::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$httpMiddlewareResolver', service(MiddlewareResolver::class))
        ->arg('$foundationFactory', service(HttpFoundationFactory::class))
        ->arg('$psrRequestFactory', service(PsrHttpFactory::class))
        ->arg('$router', service('router.default'));

    $services->set(SymfonyControllerRequestHandlerFactory::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$foundationHttpFactory', service(HttpFoundationFactory::class))
        ->arg('$psrHttpFactory', service(PsrHttpFactory::class))
        ->arg('$requestStack', service('request_stack'));

    $services->set(MiddlewareInjectionSubscriber::class)
        ->tag('kernel.event_subscriber')
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$symfonyControllerAdapter', service(SymfonyControllerAdapter::class));

    $services->set(RouteNameResolver::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$router', service('router.default'))
        ->share(false);

    $services->set(CompiledPathResolver::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->share(false);

    $services->set(StrategyResolverFactory::class)
        ->autowire(true)
        ->autoconfigure(true);

    $services->set(ListMiddlewareRulesCommand::class)
        ->tag('console.command')
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$routeNameStrategyResolver', service(RouteNameResolver::class))
        ->arg('$compiledPathStrategyResolver', service(CompiledPathResolver::class));

    $services->set(WarmUpMiddlewareCacheCommand::class)
        ->tag('console.command')
        ->autowire(true)
        ->autoconfigure(true)
        ->args([
        service('router.default'),
        service('MiddlewareChainResolverCacheRemoval'),
    ]);

    $services->set('MiddlewareChainResolver', RouteNameResolver::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->args([
        [
            service(RouteNameResolver::class),
            service(CompiledPathResolver::class),
        ],
    ])
        ->factory([
        service(StrategyResolverFactory::class),
        'create',
    ]);

    $services->set(MiddlewareResolver::class)
        ->tag('monolog.logger', [
        'channel' => 'middleware',
    ])
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$middlewareResolverChain', service('MiddlewareChainResolver'))
        ->arg('$logger', service('?logger'));

    $services->set('cache.psr15-middleware')
        ->tag('cache.pool')
        ->autowire(true)
        ->autoconfigure(true);

    $services->set('MiddlewareChainResolverCaching', MiddlewareResolverCaching::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$resolver', service(MiddlewareResolver::class))
        ->arg('$cache', service('cache.psr15-middleware'));

    $services->set('MiddlewareChainResolverCacheRemoval', MiddlewareResolverCacheRemoval::class)
        ->autowire(true)
        ->autoconfigure(true)
        ->arg('$decoratedObject', service('MiddlewareChainResolverCaching'))
        ->arg('$cache', service('cache.psr15-middleware'));

    $services->set(TestController::class)
        ->public()
        ->tag('container.service_subscriber')
        ->autowire(true)
        ->autoconfigure(true);
};
