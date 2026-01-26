<?php

declare(strict_types=1);

use Profesia\Symfony\Psr15Bundle\Tests\Acceptance\Assets\TestController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('1', '/1')
        ->controller([TestController::class, 'indexAction']);

    $routes->add('2', '/2')
        ->controller([TestController::class, 'indexAction']);

    $routes->add('3', '/3')
        ->controller([TestController::class, 'indexAction']);

    $routes->add('4', [
            'en' => '/4',
            'sk' => '{_locale}/4',
        ])
        ->controller([TestController::class, 'indexAction']);

    $routes->add('5', '/5')
        ->controller([TestController::class, 'indexAction'])
        ->locale('sk');
};
