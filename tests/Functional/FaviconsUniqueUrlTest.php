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
    public function aFileIsGeneratedOnceButKeepsEveryLinkPointingAtIt(): void
    {
        // The low resolution block declares 72x72 both as an apple-touch-icon and as an icon, for one
        // identical configuration. Both links belong in the page; the bytes belong on disk once.

        // Given
        static::bootKernel();
        $compiler = $this->createCompilerWithDarkTheme();

        // When
        $urls = [];
        $links = [];
        foreach ($compiler->getFiles() as $url => $file) {
            $urls[] = $url;
            preg_match_all('/<link[^>]*>/', (string) $file->html, $matches);
            foreach ($matches[0] as $link) {
                $links[] = $link;
            }
        }

        // Then
        static::assertSame($urls, array_unique($urls), 'a file is generated more than once');

        $rels = [];
        foreach ($links as $link) {
            if (preg_match('/sizes="72x72"/', $link) === 1 && preg_match('/rel="([^"]+)"/', $link, $m) === 1) {
                $rels[] = $m[1];
            }
        }
        static::assertContains('apple-touch-icon', $rels);
        static::assertContains('icon', $rels);
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
