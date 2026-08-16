<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Service\ScriptSection;

/**
 * @internal
 */
final class ScriptSectionTest extends TestCase
{
    #[Test]
    public function itOnlyRendersTheCodeWhenDebugIsDisabled(): void
    {
        // Given
        $section = ScriptSection::create('CACHE CLEAR', false)
            ->comment('This comment is only for the developers')
            ->code("caches.delete('foo');\n");

        // When
        $result = $section->render();

        // Then
        static::assertSame("caches.delete('foo');\n", $result);
    }

    #[Test]
    public function itSurroundsTheCodeWithBannersWhenDebugIsEnabled(): void
    {
        // Given
        $section = ScriptSection::create('CACHE CLEAR', true)
            ->code("caches.delete('foo');\n");

        // When
        $result = $section->render();

        // Then
        static::assertStringStartsWith("\n\n/*", $result);
        static::assertStringContainsString(' CACHE CLEAR ', $result);
        static::assertStringContainsString(' END CACHE CLEAR ', $result);
        static::assertStringContainsString("caches.delete('foo');\n", $result);
        static::assertStringEndsWith("\n\n\n\n", $result);
    }

    #[Test]
    public function itRendersEveryCommentLineWithItsOwnMarker(): void
    {
        // Given
        $section = ScriptSection::create('CACHE STRATEGY', true)
            ->comment('First line', "Second line\nover two rows")
            ->code('');

        // When
        $result = $section->render();

        // Then
        static::assertStringContainsString("// First line\n// Second line\n// over two rows\n\n", $result);
    }

    #[Test]
    public function itKeepsTheCodeInTheOrderItWasAdded(): void
    {
        // Given
        $section = ScriptSection::create('WORKBOX IMPORT', false)
            ->code("first();\n")
            ->comment('Ignored')
            ->code("second();\n");

        // When
        $result = $section->render();

        // Then
        static::assertSame("first();\nsecond();\n", $result);
    }

    #[Test]
    public function itCanBeCastToAString(): void
    {
        // Given
        $section = ScriptSection::create('SKIP WAITING', false)
            ->code("self.skipWaiting();\n");

        // Then
        static::assertSame($section->render(), (string) $section);
    }
}
