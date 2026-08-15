<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Service\FaviconsCompiler;
use function sprintf;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class StartupImageOrientationTest extends KernelTestCase
{
    #[Test]
    public function startupImageDimensionsMatchTheirMediaQuery(): void
    {
        // Given
        self::bootKernel();
        $compiler = self::getContainer()->get(FaviconsCompiler::class);
        static::assertInstanceOf(FaviconsCompiler::class, $compiler);

        // When
        $html = '';
        foreach ($compiler->getFiles() as $file) {
            $html .= $file->html ?? '';
        }
        preg_match_all('/<link[^>]*apple-touch-startup-image[^>]*>/', $html, $links);

        // Then
        static::assertNotEmpty($links[0], 'no startup image was generated');

        foreach ($links[0] as $link) {
            preg_match('/sizes="(\d+)x(\d+)"/', $link, $sizes);
            static::assertNotEmpty($sizes, 'no sizes attribute on: ' . $link);

            preg_match('/media="([^"]+)"/', $link, $mediaMatch);
            static::assertNotEmpty($mediaMatch, 'no media attribute on: ' . $link);
            $media = $mediaMatch[1];

            preg_match('/device-width:\s*(\d+)px/', $media, $deviceWidth);
            preg_match('/device-height:\s*(\d+)px/', $media, $deviceHeight);
            preg_match('/device-pixel-ratio:\s*(\d+)/', $media, $devicePixelRatio);
            static::assertNotEmpty($deviceWidth, 'no device-width in media: ' . $media);
            static::assertNotEmpty($deviceHeight, 'no device-height in media: ' . $media);
            static::assertNotEmpty($devicePixelRatio, 'no device-pixel-ratio in media: ' . $media);

            $dpr = (int) $devicePixelRatio[1];
            $physicalWidth = (int) $deviceWidth[1] * $dpr;
            $physicalHeight = (int) $deviceHeight[1] * $dpr;
            $isPortrait = str_contains($media, 'orientation: portrait');

            $expectedWidth = $isPortrait ? $physicalWidth : $physicalHeight;
            $expectedHeight = $isPortrait ? $physicalHeight : $physicalWidth;

            $actualWidth = (int) $sizes[1];
            $actualHeight = (int) $sizes[2];

            static::assertSame(
                [$expectedWidth, $expectedHeight],
                [$actualWidth, $actualHeight],
                sprintf(
                    'device-width: %spx, device-height: %spx, dpr: %d, %s: expected %dx%d, got %dx%d',
                    $deviceWidth[1],
                    $deviceHeight[1],
                    $dpr,
                    $isPortrait ? 'portrait' : 'landscape',
                    $expectedWidth,
                    $expectedHeight,
                    $actualWidth,
                    $actualHeight,
                )
            );
        }
    }
}
