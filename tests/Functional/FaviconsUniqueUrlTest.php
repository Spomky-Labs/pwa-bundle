<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class FaviconsUniqueUrlTest extends KernelTestCase
{
    use DarkThemeFaviconsTrait;

    #[Test]
    public function aUrlWithoutContentHashIsGeneratedOnlyOnce(): void
    {
        // A generated file name embeds a hash of its source and of its configuration, so two entries
        // sharing a name necessarily share their content. Entries served from a fixed URL have no such
        // guarantee: generating them once per color scheme makes the last one overwrite the other.

        // Given
        static::bootKernel();
        $compiler = $this->createCompilerWithDarkTheme();

        // When
        $urls = [];
        foreach ($compiler->getFiles() as $url => $file) {
            if (preg_match('/-[0-9a-f]{32}\.\w+$/', $url) === 1) {
                continue;
            }
            $urls[] = $url;
        }

        // Then
        $duplicates = array_keys(array_filter(array_count_values($urls), static fn (int $n): bool => $n > 1));
        static::assertSame(
            [],
            $duplicates,
            'these URLs carry no content hash and are generated more than once, so the last one silently '
            . 'overwrites the previous ones: ' . implode(', ', $duplicates)
        );
    }

    #[Test]
    public function theFaviconIcoIsEmittedOnceAndForEveryColorScheme(): void
    {
        // Given
        static::bootKernel();
        $compiler = $this->createCompilerWithDarkTheme();

        // When
        $medias = [];
        foreach ($compiler->getFiles() as $url => $file) {
            if ($url === '/favicon.ico') {
                $medias[] = $this->extractMedia((string) $file->html);
            }
        }

        // Then
        static::assertCount(1, $medias, '/favicon.ico is served from a fixed URL and cannot have a dark variant');
        static::assertNull($medias[0], '/favicon.ico must not be restricted to a single color scheme');
    }
}
