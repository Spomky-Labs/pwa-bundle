<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

use RuntimeException;
use function spl_object_id;
use SpomkyLabs\PwaBundle\Dto\StartupImages;
use SpomkyLabs\PwaBundle\Dto\StartupImageTheme;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\Service\SourceImageResolver;
use SpomkyLabs\PwaBundle\Service\StartupImagesBuilder;

/**
 * The historical rendering: the source image, scaled down and centered over the background color.
 *
 * It is what a startup image looks like when no template is configured, and it asks nothing more of the
 * application than the image processor the icons already need.
 */
final class IconStartupImageRenderer implements StartupImageRendererInterface
{
    private readonly StartupImages $startupImages;

    /**
     * @var array<int, string>
     */
    private array $contents = [];

    public function __construct(
        private readonly null|ImageProcessorInterface $imageProcessor,
        private readonly SourceImageResolver $sourceImageResolver,
        StartupImagesBuilder $startupImagesBuilder,
    ) {
        $this->startupImages = $startupImagesBuilder->create();
    }

    public function hash(StartupImageDefinition $definition): string
    {
        return hash('xxh128', $this->getSourceHash($definition->theme) . $this->getConfiguration($definition));
    }

    public function render(StartupImageDefinition $definition): string
    {
        if ($this->imageProcessor === null) {
            throw new RuntimeException(
                'Unable to render the startup images: no image processor is available. Set "pwa.image_processor", or describe the images with a template.'
            );
        }

        return $this->imageProcessor->process(
            $this->getContent($definition->theme),
            null,
            null,
            null,
            $this->getConfiguration($definition)
        );
    }

    private function getConfiguration(StartupImageDefinition $definition): Configuration
    {
        return Configuration::create(
            $definition->width,
            $definition->height,
            'png',
            $definition->theme->backgroundColor,
            $definition->theme->borderRadius,
            $this->getImageScale($definition),
            $this->startupImages->monochrome,
        );
    }

    /**
     * The bigger the screen, the smaller the share of it the image takes: a logo scaled to a third of an
     * iPad screen reads as a poster, where the very same share of an iPhone SE screen reads as an icon.
     */
    private function getImageScale(StartupImageDefinition $definition): int
    {
        $diagonal = sqrt($definition->width ** 2 + $definition->height ** 2);

        return (int) (30 + 10 * exp(-$diagonal / 1500));
    }

    private function getSourceHash(StartupImageTheme $theme): string
    {
        return hash('xxh128', $this->getContent($theme));
    }

    private function getContent(StartupImageTheme $theme): string
    {
        return $this->contents[spl_object_id($theme)] ??= $this->sourceImageResolver->resolve(
            $theme->src,
            $theme->svgAttributes
        )->content;
    }
}
