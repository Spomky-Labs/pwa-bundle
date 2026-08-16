<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Theme;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\FaviconsBuilder;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use SpomkyLabs\PwaBundle\Service\SourceImageResolver;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Builds a favicons compiler configured with both a light and a dark theme, which the bundle
 * test configuration does not cover.
 *
 * @internal
 */
trait DarkThemeFaviconsTrait
{
    private function createCompilerWithDarkTheme(): FaviconsCompiler
    {
        $default = new Theme();
        $default->src = Asset::create('pwa/1920x1920.svg');

        // A source of its own, so that only the URLs that structurally collide show up as duplicates.
        $dark = new Theme();
        $dark->src = Asset::create('pwa/screenshots/600x400.svg');

        $favicons = new Favicons();
        $favicons->enabled = true;
        $favicons->lowResolution = true;
        $favicons->default = $default;
        $favicons->dark = $dark;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->method('denormalize')
            ->willReturn($favicons);

        return new FaviconsCompiler(
            static::getContainer()->get(ImageProcessorInterface::class),
            new FaviconsBuilder($denormalizer, []),
            static::getContainer()->get(SourceImageResolver::class),
            static::getContainer()->get(BasePathResolver::class),
            false
        );
    }

    private function extractMedia(string $html): null|string
    {
        return preg_match('/ media="([^"]+)"/', $html, $matches) === 1 ? $matches[1] : null;
    }
}
