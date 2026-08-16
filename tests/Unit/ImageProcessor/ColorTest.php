<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ImageProcessor;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\ImageProcessor\Color;
use function sprintf;

/**
 * @internal
 */
final class ColorTest extends TestCase
{
    #[Test]
    #[DataProvider('supportedNotations')]
    public function itResolvesTheSupportedNotations(
        string $notation,
        int $red,
        int $green,
        int $blue,
        int $opacity
    ): void {
        $color = Color::fromString($notation);

        static::assertSame($red, $color->red);
        static::assertSame($green, $color->green);
        static::assertSame($blue, $color->blue);
        static::assertSame($opacity, $color->opacity);
    }

    /**
     * @return iterable<string, array{string, int, int, int, int}>
     */
    public static function supportedNotations(): iterable
    {
        yield 'six digits' => ['#ff0000', 255, 0, 0, 255];
        yield 'six digits without the hash' => ['ff0000', 255, 0, 0, 255];
        yield 'six digits in upper case' => ['#F5EF06', 245, 239, 6, 255];
        yield 'three digits' => ['#f00', 255, 0, 0, 255];
        yield 'three digits mixing channels' => ['#1a2', 17, 170, 34, 255];
        yield 'eight digits, fully opaque' => ['#ff0000ff', 255, 0, 0, 255];
        yield 'eight digits, half transparent' => ['#ff000080', 255, 0, 0, 128];
        yield 'eight digits, fully transparent' => ['#ff000000', 255, 0, 0, 0];
        yield 'four digits' => ['#f00c', 255, 0, 0, 204];
        yield 'the transparent keyword' => ['transparent', 0, 0, 0, 0];
        // The configuration documents colour names: ->example(['red', '#f5ef06']).
        yield 'a colour name' => ['red', 255, 0, 0, 255];
        yield 'a colour name in upper case' => ['WHITE', 255, 255, 255, 255];
        yield 'a colour name with surrounding spaces' => [' rebeccapurple ', 102, 51, 153, 255];
        yield 'the CSS grey, not the X11 one' => ['grey', 128, 128, 128, 255];
        // The functional notations only ImageMagick used to accept.
        yield 'rgb()' => ['rgb(255, 0, 0)', 255, 0, 0, 255];
        yield 'rgb() without spaces' => ['rgb(12,34,56)', 12, 34, 56, 255];
        yield 'rgb() with percentages' => ['rgb(100%, 0%, 50%)', 255, 0, 128, 255];
        yield 'rgba()' => ['rgba(255, 0, 0, 0.5)', 255, 0, 0, 128];
        yield 'rgb() with the modern separators' => ['rgb(255 0 0 / 50%)', 255, 0, 0, 128];
        yield 'hsl() red' => ['hsl(0, 100%, 50%)', 255, 0, 0, 255];
        yield 'hsl() green' => ['hsl(120, 100%, 50%)', 0, 255, 0, 255];
        yield 'hsl() blue' => ['hsl(240deg, 100%, 50%)', 0, 0, 255, 255];
        yield 'hsl() grey' => ['hsl(0, 0%, 50%)', 128, 128, 128, 255];
        yield 'hsla()' => ['hsla(0, 100%, 50%, 0.5)', 255, 0, 0, 128];
    }

    #[Test]
    #[DataProvider('gdAlphas')]
    public function itStatesTheTransparencyOnTheScaleGdUses(string $notation, int $expected): void
    {
        static::assertSame($expected, Color::fromString($notation)->gdAlpha());
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function gdAlphas(): iterable
    {
        yield 'opaque' => ['#ff0000', 0];
        yield 'half transparent' => ['#ff000080', 63];
        yield 'fully transparent' => ['transparent', 127];
    }

    #[Test]
    public function itRendersTheNotationImagickUnderstands(): void
    {
        static::assertSame('#FF0000FF', Color::fromString('red')->toHex());
        static::assertSame('#FF000080', Color::fromString('#ff000080')->toHex());
    }

    #[Test]
    #[DataProvider('unsupportedNotations')]
    public function itRejectsWhatItCannotResolve(string $notation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The color "%s" is not supported.', $notation));

        Color::fromString($notation);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedNotations(): iterable
    {
        yield 'an unknown name' => ['not-a-color'];
        yield 'an empty string' => [''];
        yield 'too few digits' => ['#ff'];
        yield 'too many digits' => ['#ff0000ff00'];
        yield 'an odd digit count' => ['#ff000'];
        yield 'a non hexadecimal digit' => ['#ff00zz'];
        yield 'an unknown function' => ['cmyk(0, 1, 1, 0)'];
        yield 'a functional notation with too few arguments' => ['rgb(255, 0)'];
        yield 'a functional notation with too many arguments' => ['rgb(255, 0, 0, 1, 1)'];
        yield 'a functional notation with a non numeric argument' => ['rgb(255, zero, 0)'];
        yield 'an unclosed functional notation' => ['rgb(255, 0, 0'];
    }
}
