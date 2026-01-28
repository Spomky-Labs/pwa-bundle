<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\Dto;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\Dto\ScreenshotDimension;

/**
 * @internal
 */
final class ScreenshotDimensionTest extends TestCase
{
    #[Test]
    public function itExpandsProfileWithoutOrientationSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('ipad');

        static::assertCount(1, $dimensions);
        static::assertSame(768, $dimensions[0]->getDimensions()['width']);
        static::assertSame(1024, $dimensions[0]->getDimensions()['height']);
    }

    #[Test]
    public function itExpandsProfileWithLandscapeSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('ipad/L');

        static::assertCount(1, $dimensions);
        static::assertSame(1024, $dimensions[0]->getDimensions()['width']);
        static::assertSame(768, $dimensions[0]->getDimensions()['height']);
        static::assertStringContainsString('Landscape', $dimensions[0]->getLabel());
    }

    #[Test]
    public function itExpandsProfileWithPortraitSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('ipad/P');

        static::assertCount(1, $dimensions);
        static::assertSame(768, $dimensions[0]->getDimensions()['width']);
        static::assertSame(1024, $dimensions[0]->getDimensions()['height']);
        static::assertStringContainsString('Portrait', $dimensions[0]->getLabel());
    }

    #[Test]
    public function itExpandsProfileWithBothOrientationsSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('ipad/LP');

        static::assertCount(2, $dimensions);

        // First is portrait
        static::assertSame(768, $dimensions[0]->getDimensions()['width']);
        static::assertSame(1024, $dimensions[0]->getDimensions()['height']);
        static::assertStringContainsString('Portrait', $dimensions[0]->getLabel());

        // Second is landscape
        static::assertSame(1024, $dimensions[1]->getDimensions()['width']);
        static::assertSame(768, $dimensions[1]->getDimensions()['height']);
        static::assertStringContainsString('Landscape', $dimensions[1]->getLabel());
    }

    #[Test]
    public function itExpandsExplicitDimensionsWithoutOrientationSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('1920x1080');

        static::assertCount(1, $dimensions);
        static::assertSame(1920, $dimensions[0]->getDimensions()['width']);
        static::assertSame(1080, $dimensions[0]->getDimensions()['height']);
    }

    #[Test]
    public function itExpandsExplicitDimensionsWithLandscapeSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('1920x1080/L');

        static::assertCount(1, $dimensions);
        static::assertSame(1920, $dimensions[0]->getDimensions()['width']);
        static::assertSame(1080, $dimensions[0]->getDimensions()['height']);
    }

    #[Test]
    public function itExpandsExplicitDimensionsWithPortraitSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('1920x1080/P');

        static::assertCount(1, $dimensions);
        static::assertSame(1080, $dimensions[0]->getDimensions()['width']);
        static::assertSame(1920, $dimensions[0]->getDimensions()['height']);
    }

    #[Test]
    public function itExpandsExplicitDimensionsWithBothOrientationsSelector(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('1920x1080/LP');

        static::assertCount(2, $dimensions);

        // First is portrait
        static::assertSame(1080, $dimensions[0]->getDimensions()['width']);
        static::assertSame(1920, $dimensions[0]->getDimensions()['height']);

        // Second is landscape
        static::assertSame(1920, $dimensions[1]->getDimensions()['width']);
        static::assertSame(1080, $dimensions[1]->getDimensions()['height']);
    }

    #[Test]
    public function itHandlesCaseInsensitiveOrientationSelectors(): void
    {
        $dimensions1 = ScreenshotDimension::expandFromString('ipad/l');
        $dimensions2 = ScreenshotDimension::expandFromString('ipad/L');
        $dimensions3 = ScreenshotDimension::expandFromString('ipad/lp');
        $dimensions4 = ScreenshotDimension::expandFromString('ipad/LP');

        static::assertCount(1, $dimensions1);
        static::assertCount(1, $dimensions2);
        static::assertCount(2, $dimensions3);
        static::assertCount(2, $dimensions4);
    }

    #[Test]
    public function itReturnsEmptyArrayForInvalidSize(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('invalid-size');

        static::assertCount(0, $dimensions);
    }

    #[Test]
    public function itReturnsEmptyArrayForInvalidSizeWithOrientation(): void
    {
        $dimensions = ScreenshotDimension::expandFromString('invalid-size/LP');

        static::assertCount(0, $dimensions);
    }
}
