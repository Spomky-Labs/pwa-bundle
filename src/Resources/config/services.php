<?php

declare(strict_types=1);

use SpomkyLabs\PwaBundle\Command\GenerateManifestCommand;
use SpomkyLabs\PwaBundle\Command\GenerateServiceWorkerCommand;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\ActionsSectionProcessor;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\ApplicationIconsSectionProcessor;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\ApplicationScreenshotsSectionProcessor;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\ServiceWorkerSectionProcessor;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\ShortcutsSectionProcessor;
use SpomkyLabs\PwaBundle\Command\SectionProcessor\Windows10WidgetsSectionProcessor;
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

    $container->set(ApplicationIconsSectionProcessor::class)
        ->tag('pwa.section-processor');
    $container->set(ApplicationScreenshotsSectionProcessor::class)
        ->tag('pwa.section-processor');
    $container->set(ShortcutsSectionProcessor::class)
        ->tag('pwa.section-processor');
    $container->set(ActionsSectionProcessor::class)
        ->tag('pwa.section-processor');
    $container->set(Windows10WidgetsSectionProcessor::class)
        ->tag('pwa.section-processor');
    $container->set(ServiceWorkerSectionProcessor::class)
        ->tag('pwa.section-processor');

    $container->set(GenerateManifestCommand::class);
    $container->set(GenerateServiceWorkerCommand::class);

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
