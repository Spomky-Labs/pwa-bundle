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
final class FaviconsColorSchemeTest extends KernelTestCase
{
    use DarkThemeFaviconsTrait;

    #[Test]
    public function faviconsAreDeclinedForBothColorSchemes(): void
    {
        // Given
        static::bootKernel();
        $compiler = $this->createCompilerWithDarkTheme();

        // When
        $medias = $this->getMediaAttributes($compiler, 'apple-touch-icon');

        // Then
        static::assertNotEmpty($medias, 'no apple touch icon was generated');
        static::assertContains('(prefers-color-scheme: light)', $medias);
        static::assertContains('(prefers-color-scheme: dark)', $medias);
    }

    /**
     * @return array<string>
     */
    private function getMediaAttributes(FaviconsCompiler $compiler, string $rel): array
    {
        $medias = [];
        foreach ($compiler->getFiles() as $file) {
            $html = (string) $file->html;
            if (! str_contains($html, sprintf('rel="%s"', $rel))) {
                continue;
            }
            $media = $this->extractMedia($html);
            if ($media !== null) {
                $medias[] = $media;
            }
        }

        return $medias;
    }
}
