<?php

declare(strict_types=1);

use Facebook\WebDriver\WebDriverDimension;
use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategiesInterface;
use SpomkyLabs\PwaBundle\CachingStrategy\PreloadUrlsGeneratorManager;
use SpomkyLabs\PwaBundle\CachingStrategy\PreloadUrlsTagGenerator;
use SpomkyLabs\PwaBundle\Command\CreateIconsCommand;
use SpomkyLabs\PwaBundle\Command\CreateScreenshotCommand;
use SpomkyLabs\PwaBundle\Command\ListCacheStrategiesCommand;
use SpomkyLabs\PwaBundle\DataCollector\PwaCollector;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\EventSubscriber\ScreenshotSubscriber;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use SpomkyLabs\PwaBundle\MatchCallbackHandler\MatchCallbackHandlerInterface;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerCompiler;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\ServiceWorkerRuleInterface;
use SpomkyLabs\PwaBundle\Subscriber\ManifestCompileEventListener;
use SpomkyLabs\PwaBundle\Subscriber\PwaDevServerSubscriber;
use SpomkyLabs\PwaBundle\Subscriber\ServiceWorkerCompileEventListener;
use SpomkyLabs\PwaBundle\Subscriber\WorkboxCompileEventListener;
use SpomkyLabs\PwaBundle\Twig\InstanceOfExtension;
use SpomkyLabs\PwaBundle\Twig\PwaExtension;
use SpomkyLabs\PwaBundle\Twig\PwaRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Panther\Client;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $container = $configurator->services()
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
    if (class_exists(Client::class) && class_exists(WebDriverDimension::class) && class_exists(MimeTypes::class)) {
        $container->set(CreateScreenshotCommand::class);
    }
    if (class_exists(MimeTypes::class)) {
        $container->set(CreateIconsCommand::class);
    }
    $container->set(ListCacheStrategiesCommand::class);

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
    $container->set(ManifestCompileEventListener::class);
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

    /*** Service Worker Compiler Rules ***/
    $container->instanceof(ServiceWorkerRuleInterface::class)
        ->tag('spomky_labs_pwa.service_worker_rule')
    ;
    $container->load('SpomkyLabs\\PwaBundle\\ServiceWorkerRule\\', '../../ServiceWorkerRule/*');

    $container->instanceof(HasCacheStrategiesInterface::class)
        ->tag('spomky_labs_pwa.cache_strategy')
    ;
    $container->load('SpomkyLabs\\PwaBundle\\CachingStrategy\\', '../../CachingStrategy/*');

    $container->instanceof(MatchCallbackHandlerInterface::class)
        ->tag('spomky_labs_pwa.match_callback_handler')
    ;
    $container->load('SpomkyLabs\\PwaBundle\\MatchCallbackHandler\\', '../../MatchCallbackHandler/*');

    $container->set(PreloadUrlsGeneratorManager::class);
    $container->instanceof(UrlGeneratorInterface::class)
        ->tag('spomky_labs_pwa.preload_urls_generator')
    ;
    $container->set(PreloadUrlsTagGenerator::class)
        ->abstract()
        ->args([
            '$alias' => abstract_arg('alias'),
            '$urls' => abstract_arg('urls'),
        ])
    ;
    $container->set(ScreenshotSubscriber::class);

    if ($configurator->env() !== 'prod') {
        $container->set(PwaCollector::class)
            ->tag('data_collector', [
                'template' => '@SpomkyLabsPwa/Collector/template.html.twig',
                'id' => 'pwa',
            ])
        ;
        $container->set(InstanceOfExtension::class);
    }
};
