<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\StartupImages;
use SpomkyLabs\PwaBundle\Dto\StartupImageTheme;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use SpomkyLabs\PwaBundle\Service\SourceImageResolver;
use SpomkyLabs\PwaBundle\Service\StartupImagesBuilder;
use SpomkyLabs\PwaBundle\Service\StartupImagesCompiler;
use SpomkyLabs\PwaBundle\StartupImage\DeviceCatalog;
use SpomkyLabs\PwaBundle\StartupImage\HtmlRendererInterface;
use SpomkyLabs\PwaBundle\StartupImage\IconStartupImageRenderer;
use SpomkyLabs\PwaBundle\StartupImage\TemplateStartupImageRenderer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Builds a startup images compiler out of a hand-written configuration, which lets a test declare a dark
 * color scheme or a template without the whole test kernel having to.
 *
 * @internal
 */
trait StartupImagesCompilerTrait
{
    /**
     * @param array<string, mixed> $context
     */
    private function createStartupImagesCompiler(
        bool $withDarkTheme = false,
        null|string $template = null,
        null|HtmlRendererInterface $htmlRenderer = null,
        array $context = [],
        bool $withTwig = true,
    ): StartupImagesCompiler {
        $default = new StartupImageTheme();
        $default->src = Asset::create('pwa/1920x1920.svg');
        $default->backgroundColor = '#ffffff';
        $default->context = $context;

        $startupImages = new StartupImages();
        $startupImages->enabled = true;
        $startupImages->template = $template;
        $startupImages->default = $default;

        if ($withDarkTheme) {
            // A source of its own, so that only the URLs that structurally collide show up as duplicates.
            $dark = new StartupImageTheme();
            $dark->src = Asset::create('pwa/screenshots/600x400.svg');
            $dark->backgroundColor = '#000000';
            $dark->context = $context;
            $startupImages->dark = $dark;
        }

        $denormalizer = $this->createStub(DenormalizerInterface::class);
        $denormalizer->method('denormalize')
            ->willReturn($startupImages);
        $builder = new StartupImagesBuilder($denormalizer, []);

        $container = static::getContainer();
        $sourceImageResolver = $container->get(SourceImageResolver::class);

        return new StartupImagesCompiler(
            $builder,
            new DeviceCatalog(),
            new IconStartupImageRenderer(
                $container->get(ImageProcessorInterface::class),
                $sourceImageResolver,
                $builder
            ),
            new TemplateStartupImageRenderer(
                $withTwig ? $container->get('twig') : null,
                $htmlRenderer,
                $sourceImageResolver,
                $container->get(ManifestBuilder::class),
                $builder
            ),
            $container->get(BasePathResolver::class),
            false
        );
    }
}
