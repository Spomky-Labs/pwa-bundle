<?php

declare(strict_types=1);

use SpomkyLabs\PwaBundle\Command\GenerateManifestCommand;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(GenerateManifestCommand::class);

    if (extension_loaded('imagick')) {
        $container->set(ImagickImageProcessor::class);
    }
    if (extension_loaded('gd')) {
        $container->set(GDImageProcessor::class);
    }
};
