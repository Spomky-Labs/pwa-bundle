<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Service\StartupImagesCompiler;
use function sprintf;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class StartupImageDeviceCoverageTest extends KernelTestCase
{
    #[Test]
    public function everyDeviceIsCoveredInBothOrientationsExactlyOnce(): void
    {
        // Given
        static::bootKernel();
        $compiler = static::getContainer()->get(StartupImagesCompiler::class);
        static::assertInstanceOf(StartupImagesCompiler::class, $compiler);

        // When
        $medias = [];
        foreach ($compiler->getFiles() as $file) {
            $html = (string) $file->html;
            if (! str_contains($html, 'apple-touch-startup-image')) {
                continue;
            }
            if (preg_match('/ media="([^"]+)"/', $html, $matches) === 1) {
                $medias[] = $matches[1];
            }
        }

        // Then
        static::assertNotEmpty($medias, 'no startup image was generated');

        $duplicates = array_keys(array_filter(array_count_values($medias), static fn (int $n): bool => $n > 1));
        static::assertSame([], $duplicates, 'these media queries are declared twice: ' . implode(', ', $duplicates));

        $devices = [];
        foreach ($medias as $media) {
            $devices[preg_replace('/ and \(orientation: \w+\)$/', '', $media)][] = $media;
        }
        foreach ($devices as $device => $orientations) {
            static::assertCount(
                2,
                $orientations,
                sprintf('"%s" must be declared in portrait and in landscape', $device)
            );
        }
    }
}
