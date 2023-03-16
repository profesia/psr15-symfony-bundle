<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('1', '/1')
        ->controller(sprintf('%s::%s', Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets\TestController::class, 'indexAction'));

    $routingConfigurator->add('2', '/2')
        ->controller(sprintf('%s::%s', Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets\TestController::class, 'indexAction'));

    $routingConfigurator->add('3', '/3')
        ->controller(sprintf('%s::%s', Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets\TestController::class, 'indexAction'));
};
