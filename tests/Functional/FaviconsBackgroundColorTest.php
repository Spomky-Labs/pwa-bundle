<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\Dto\Favicons;
use SpomkyLabs\PwaBundle\Dto\Manifest;
use SpomkyLabs\PwaBundle\Service\FaviconsBuilder;
use SpomkyLabs\PwaBundle\Service\ManifestBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @internal
 */
final class FaviconsBackgroundColorTest extends KernelTestCase
{
    #[Test]
    public function theFaviconsInheritTheManifestBackgroundColor(): void
    {
        // The test configuration declares "red" under the manifest and nothing under the favicons.

        // Given
        static::bootKernel();
        $manifest = static::getContainer()
            ->get(ManifestBuilder::class)
            ->create();
        $favicons = static::getContainer()
            ->get(FaviconsBuilder::class)
            ->create();

        // Then
        static::assertInstanceOf(Manifest::class, $manifest);
        static::assertInstanceOf(Favicons::class, $favicons);
        static::assertSame('red', $manifest->backgroundColor);
        static::assertSame(
            $manifest->backgroundColor,
            $favicons->default->backgroundColor,
            'a favicon without a background color of its own must fall back to the manifest one, otherwise it '
            . 'is generated over a fully transparent background'
        );
    }
}
