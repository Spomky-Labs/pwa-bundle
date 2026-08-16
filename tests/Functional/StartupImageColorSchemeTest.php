<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Service\StartupImagesCompiler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class StartupImageColorSchemeTest extends KernelTestCase
{
    use StartupImagesCompilerTrait;

    #[Test]
    public function mediaQueriesAreNotNested(): void
    {
        // Given
        static::bootKernel();
        $compiler = $this->createStartupImagesCompiler(withDarkTheme: true);

        // When
        $medias = $this->getMediaAttributes($compiler);

        // Then
        static::assertNotEmpty($medias, 'no startup image was generated');
        foreach ($medias as $media) {
            static::assertStringStartsNotWith(
                '((',
                $media,
                'the media query nests a condition in parentheses, which Safari only parses from 16.4 on: ' . $media
            );
            static::assertMatchesRegularExpression(
                '/^\(device-width: .+\) and \(prefers-color-scheme: (light|dark)\)$/',
                $media
            );
        }
    }

    #[Test]
    public function aSingleColorSchemeCarriesNoColorSchemeCondition(): void
    {
        // Given
        static::bootKernel();
        $compiler = $this->createStartupImagesCompiler();

        // When
        $medias = $this->getMediaAttributes($compiler);

        // Then
        static::assertNotEmpty($medias, 'no startup image was generated');
        foreach ($medias as $media) {
            static::assertStringNotContainsString('prefers-color-scheme', $media);
        }
    }

    #[Test]
    public function eachColorSchemeGetsItsOwnImage(): void
    {
        // Given
        static::bootKernel();
        $compiler = $this->createStartupImagesCompiler(withDarkTheme: true);

        // When
        $urls = [];
        foreach ($compiler->getFiles() as $url => $file) {
            $urls[] = $url;
        }

        // Then
        static::assertSame($urls, array_unique($urls), 'a startup image is generated more than once');
        static::assertCount(84, $urls, '21 devices, two orientations, two color schemes');
    }

    /**
     * @return array<string>
     */
    private function getMediaAttributes(StartupImagesCompiler $compiler): array
    {
        $medias = [];
        foreach ($compiler->getFiles() as $file) {
            if (preg_match('/ media="([^"]+)"/', (string) $file->html, $matches) === 1) {
                $medias[] = $matches[1];
            }
        }

        return $medias;
    }
}
