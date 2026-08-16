<?php

declare(strict_types=1);

use Facebook\WebDriver\WebDriverDimension;
use SpomkyLabs\PwaBundle\CachingStrategy\HasCacheStrategiesInterface;
use SpomkyLabs\PwaBundle\CachingStrategy\PreloadUrlsGeneratorInterface;
use SpomkyLabs\PwaBundle\CachingStrategy\PreloadUrlsGeneratorManager;
use SpomkyLabs\PwaBundle\CachingStrategy\PreloadUrlsTagGeneratorFactory;
use SpomkyLabs\PwaBundle\Command\CompileCommand;
use SpomkyLabs\PwaBundle\Command\CreateIconsCommand;
use SpomkyLabs\PwaBundle\Command\CreateScreenshotCommand;
use SpomkyLabs\PwaBundle\Command\ListCacheStrategiesCommand;
use SpomkyLabs\PwaBundle\CompilerPass\LoggerCompilerPass;
use SpomkyLabs\PwaBundle\DataCollector\PwaCollector;
use SpomkyLabs\PwaBundle\EventListener\FileCompileEventListener;
use SpomkyLabs\PwaBundle\EventListener\ManifestScreenshotListener;
use SpomkyLabs\PwaBundle\EventListener\PwaDevServerListener;
use SpomkyLabs\PwaBundle\EventListener\ScreenshotListener;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use SpomkyLabs\PwaBundle\MatchCallbackHandler\MatchCallbackHandlerInterface;
use SpomkyLabs\PwaBundle\Service\ApplicationIconCompiler;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\CanLogInterface;
use SpomkyLabs\PwaBundle\Service\FaviconsBuilder;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use SpomkyLabs\PwaBundle\Service\FileCompiler;
use SpomkyLabs\PwaBundle\Service\FileCompilerInterface;
use SpomkyLabs\PwaBundle\Service\IconResolver;
use SpomkyLabs\PwaBundle\Service\LocalizedMembersBuilder;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use SpomkyLabs\PwaBundle\Service\ManifestCompiler;
use SpomkyLabs\PwaBundle\Service\ResourceHintsBuilder;
use SpomkyLabs\PwaBundle\Service\ScreenshotAttributeCollector;
use SpomkyLabs\PwaBundle\Service\ScreenshotGenerator;
use SpomkyLabs\PwaBundle\Service\ScreenshotUrlGenerator;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerBuilder;
use SpomkyLabs\PwaBundle\Service\ServiceWorkerCompiler;
use SpomkyLabs\PwaBundle\Service\SourceImageResolver;
use SpomkyLabs\PwaBundle\Service\SpeculationRulesBuilder;
use SpomkyLabs\PwaBundle\Service\StartupImagesBuilder;
use SpomkyLabs\PwaBundle\Service\StartupImagesCompiler;
use SpomkyLabs\PwaBundle\Service\TextDirectionResolver;
use SpomkyLabs\PwaBundle\ServiceWorkerRule\ServiceWorkerRuleInterface;
use SpomkyLabs\PwaBundle\StartupImage\ChromeHtmlRenderer;
use SpomkyLabs\PwaBundle\StartupImage\DeviceCatalog;
use SpomkyLabs\PwaBundle\StartupImage\HtmlRendererInterface;
use SpomkyLabs\PwaBundle\StartupImage\IconStartupImageRenderer;
use SpomkyLabs\PwaBundle\StartupImage\TemplateStartupImageRenderer;
use SpomkyLabs\PwaBundle\Twig\InstanceOfExtension;
use SpomkyLabs\PwaBundle\Twig\PwaExtension;
use SpomkyLabs\PwaBundle\Twig\PwaRuntime;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Panther\Client;
use Symfony\UX\Icons\IconRendererInterface;

return static function (ContainerConfigurator $configurator): void {
    $container = $configurator->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire()
    ;

    $container->instanceof(CanLogInterface::class)->tag(LoggerCompilerPass::TAG);
    $container->instanceof(FileCompilerInterface::class)->tag('spomky_labs_pwa.compiler');

    /* Base path */
    $container->set(BasePathResolver::class)
        ->args([
            '$context' => service('assets.context')
                ->nullOnInvalid(),
        ])
    ;

    /* Manifest */
    $container->set(ManifestBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.manifest.config'),
        ])
    ;
    $container->set(ManifestCompiler::class);
    $container->set(TextDirectionResolver::class)
        ->args([
            '$directions' => param('spomky_labs_pwa.manifest.locale_directions'),
        ])
    ;
    $container->set(LocalizedMembersBuilder::class)
        ->args([
            '$translator' => service('translator')
                ->nullOnInvalid(),
        ])
    ;

    /* Favicons */
    $container->set(FaviconsBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.favicons.config'),
        ])
    ;
    $container->set(FaviconsCompiler::class);

    /* Startup images */
    $container->set(StartupImagesBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.startup_images.config'),
        ])
    ;
    $container->set(DeviceCatalog::class);
    $container->set(IconStartupImageRenderer::class);
    $container->set(TemplateStartupImageRenderer::class)
        ->args([
            '$twig' => service('twig')->nullOnInvalid(),
            '$htmlRenderer' => service(HtmlRendererInterface::class)->nullOnInvalid(),
        ])
    ;
    if (class_exists(Client::class) && class_exists(WebDriverDimension::class)) {
        $container->set(ChromeHtmlRenderer::class)
            ->args([
                '$webClient' => service('pwa.web_client')->nullOnInvalid(),
            ])
        ;
        $container->alias(HtmlRendererInterface::class, ChromeHtmlRenderer::class);
    }
    $container->set(StartupImagesCompiler::class);

    $container->set(SourceImageResolver::class)
        ->args([
            '$iconRenderer' => service(IconRendererInterface::class)->nullOnInvalid(),
        ])
    ;
    $container->set(IconResolver::class);
    $container->set(ApplicationIconCompiler::class);
    $container->set(ScreenshotUrlGenerator::class);
    $container->set(ScreenshotAttributeCollector::class);

    /* Service Worker */
    $container->set(ServiceWorkerBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.sw.config'),
        ])
    ;
    $container->set(ServiceWorkerCompiler::class);

    /* Resource Hints */
    $container->set(ResourceHintsBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.resource_hints.config'),
            '$workboxConfig' => param('spomky_labs_pwa.sw.config'),
        ])
    ;

    /* Speculation Rules */
    $container->set(SpeculationRulesBuilder::class)
        ->args([
            '$config' => param('spomky_labs_pwa.speculation_rules.config'),
        ])
    ;

    /* Commands */
    $container->set(CompileCommand::class);
    if (class_exists(Client::class) && class_exists(WebDriverDimension::class) && class_exists(MimeTypes::class)) {
        $container->set(CreateScreenshotCommand::class);
        $container->set(ScreenshotGenerator::class);
    }
    if (class_exists(MimeTypes::class)) {
        $container->set(CreateIconsCommand::class);
    }
    $container->set(ListCacheStrategiesCommand::class);

    /* Normalizers */
    $container->load('SpomkyLabs\\PwaBundle\\Normalizer\\', '../../Normalizer/*')
        ->tag('serializer.normalizer', [
            'priority' => 1024,
        ])
    ;

    /* Image Processors */
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

    /* Event Listeners and Subscribers */
    $container->set(FileCompiler::class);
    $container->set(FileCompileEventListener::class);
    $container->set(ManifestScreenshotListener::class);
    $container->set(PwaDevServerListener::class)
        ->args([
            '$profiler' => service('profiler')
                ->nullOnInvalid(),
        ])
    ;

    $container->set(PwaExtension::class)
        ->tag('twig.extension')
    ;
    $container->set(PwaRuntime::class)
        ->tag('twig.runtime')
    ;

    /* Service Worker Compiler Rules */
    $container->instanceof(ServiceWorkerRuleInterface::class)
        ->tag('spomky_labs_pwa.service_worker_rule')
    ;
    $container->load('SpomkyLabs\\PwaBundle\\ServiceWorkerRule\\', '../../ServiceWorkerRule/*');

    $container->instanceof(HasCacheStrategiesInterface::class)
        ->tag('spomky_labs_pwa.cache_strategy')
    ;
    $container->load('SpomkyLabs\\PwaBundle\\CachingStrategy\\', '../../CachingStrategy/*')
        ->exclude('../../CachingStrategy/PreloadUrlsTagGenerator.php')
    ;

    $container->instanceof(MatchCallbackHandlerInterface::class)
        ->tag('spomky_labs_pwa.match_callback_handler')
    ;
    $container->load('SpomkyLabs\\PwaBundle\\MatchCallbackHandler\\', '../../MatchCallbackHandler/*');

    $container->set(PreloadUrlsTagGeneratorFactory::class);
    $container->set(PreloadUrlsGeneratorManager::class);
    $container->instanceof(PreloadUrlsGeneratorInterface::class)
        ->tag('spomky_labs_pwa.preload_urls_generator')
    ;

    if ($configurator->env() !== 'prod') {
        $container->set(ScreenshotListener::class);
        $container->set(PwaCollector::class)
            ->tag('data_collector', [
                'template' => '@SpomkyLabsPwa/Collector/template.html.twig',
                'id' => 'pwa',
            ])
        ;
        $container->set(InstanceOfExtension::class);
    }
};
