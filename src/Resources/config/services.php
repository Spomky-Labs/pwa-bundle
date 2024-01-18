<?php

declare(strict_types=1);

use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use SpomkyLabs\PwaBundle\Service\Builder;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\Subscriber\AssetsCompileEventListener;
use SpomkyLabs\PwaBundle\Subscriber\PwaDevServerSubscriber;
use SpomkyLabs\PwaBundle\Subscriber\ServiceWorkerCompileEventListener;
use SpomkyLabs\PwaBundle\Twig\PwaExtension;
use SpomkyLabs\PwaBundle\Twig\PwaRuntime;
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

    $container->set(AssetsCompileEventListener::class);
    $container->set(ServiceWorkerCompileEventListener::class);
    $container->set(ServiceWorkerBuilder::class);

    $container->set(PwaDevServerSubscriber::class)
        ->args([
            '$profiler' => service('profiler')
                ->nullOnInvalid(),
        ])
        ->tag('kernel.event_subscriber')
    ;

    $container->set(PwaExtension::class)
        ->tag('twig.extension')
    ;
    $container->set(PwaRuntime::class)
        ->tag('twig.runtime')
    ;
};
