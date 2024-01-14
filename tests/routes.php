<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->import(
        resource: [
            'path' => __DIR__ . '/Controller/',
            'namespace' => 'SpomkyLabs\\PwaBundle\\Tests\\Controller\\',
        ],
        type: 'attribute',
    );
};
