<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Dto\Asset;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Theme;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\Service\BasePathResolver;
use SpomkyLabs\PwaBundle\Service\FaviconsBuilder;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
final class FaviconsCacheControlTest extends KernelTestCase
{
    #[Test]
    public function onlyTheHashedFilesAreCachedAsImmutable(): void
    {
        // Given
        static::bootKernel();
        $compiler = $this->createDebugCompiler();

        // When
        $cacheControls = [];
        foreach ($compiler->getFiles() as $url => $file) {
            $cacheControls[$url] = $file->headers['Cache-Control'] ?? null;
        }
        $fixedUrlCacheControl = $cacheControls['/favicon.ico'];
        unset($cacheControls['/favicon.ico']);
        $hashedUrlCacheControls = array_values(array_unique($cacheControls));

        // Then
        static::assertSame('no-cache', $fixedUrlCacheControl);
        static::assertSame(['public, max-age=604800, immutable'], $hashedUrlCacheControls);
    }

    private function createDebugCompiler(): FaviconsCompiler
    {
        $default = new Theme();
        $default->src = Asset::create('pwa/1920x1920.svg');

        $favicons = new Favicons();
        $favicons->enabled = true;
        $favicons->default = $default;

        $denormalizer = $this->createMock(DenormalizerInterface::class);
        $denormalizer->method('denormalize')
            ->willReturn($favicons);

        return new FaviconsCompiler(
            static::getContainer()->get(ImageProcessorInterface::class),
            new FaviconsBuilder($denormalizer, []),
            static::getContainer()->get('asset_mapper'),
            null,
            static::getContainer()->get(BasePathResolver::class),
            true
        );
    }
}
