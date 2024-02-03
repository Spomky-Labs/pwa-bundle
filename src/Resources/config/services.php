<?php

declare(strict_types=1);

use Facebook\WebDriver\WebDriverDimension;
use SpomkyLabs\PwaBundle\Command\CreateIconsCommand;
use SpomkyLabs\PwaBundle\Command\CreateScreenshotCommand;
use SpomkyLabs\PwaBundle\Command\CreateServiceWorkerCommand;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerCompiler;
use SpomkyLabs\PwaBundle\Subscriber\AssetsCompileEventListener;
use SpomkyLabs\PwaBundle\Subscriber\PwaDevServerSubscriber;
use SpomkyLabs\PwaBundle\Subscriber\ServiceWorkerCompileEventListener;
use SpomkyLabs\PwaBundle\Subscriber\WorkboxCompileEventListener;
use SpomkyLabs\PwaBundle\Twig\PwaExtension;
use SpomkyLabs\PwaBundle\Twig\PwaRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Panther\Client;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    /*** Manifest ***/
    $container->set(ManifestBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.manifest.config'),
        ])
    ;
    $container->set(Manifest::class)
        ->factory([service(ManifestBuilder::class), 'create'])
    ;

    /*** Service Worker ***/
    $container->set(ServiceWorkerBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.sw.config'),
        ])
    ;
    $container->set(ServiceWorker::class)
        ->factory([service(ServiceWorkerBuilder::class), 'create'])
    ;
    $container->set(ServiceWorkerCompiler::class)
    ;

    /*** Commands ***/
    $container->set(CreateServiceWorkerCommand::class);
    if (class_exists(Client::class) && class_exists(WebDriverDimension::class) && class_exists(MimeTypes::class)) {
        $container->set(CreateScreenshotCommand::class);
    }
    if (class_exists(MimeTypes::class)) {
        $container->set(CreateIconsCommand::class);
    }

    /*** Normalizers ***/
    $container->load('SpomkyLabs\\PwaBundle\\Normalizer\\', '../../Normalizer/*')
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ])
    ;

    /*** Image Processors ***/
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

    /*** Event Listeners and Subscribers ***/
    $container->set(WorkboxCompileEventListener::class);
    $container->set(AssetsCompileEventListener::class);
    $container->set(ServiceWorkerCompileEventListener::class);
    $container->set(ServiceWorkerCompiler::class);

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
