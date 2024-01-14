<?php

declare(strict_types=1);

use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use SpomkyLabs\PwaBundle\Service\Builder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->set(Builder::class)
        ->args([
            '$config' => '%spomky_labs_pwa.config%',
        ])
    ;
    $container->set(Manifest::class)
        ->factory([service(Builder::class), 'createManifest'])
    ;

    $container->load('SpomkyLabs\\PwaBundle\\Command\\', '../../Command/*');

    $container->load('SpomkyLabs\\PwaBundle\\Normalizer\\', '../../Normalizer/*')
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ])
    ;

    if (extension_loaded('imagick')) {
        $container
            ->set(ImagickImageProcessor::class)
            ->alias('pwa.image_processor.imagick', ImagickImageProcessor::class)
        ;
    }
    if (extension_loaded('gd')) {
        $container
            ->set(GDImageProcessor::class)
            ->alias('pwa.image_processor.gd', GDImageProcessor::class)
        ;
    }
};
