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
        int $alpha
    ): void {
        $color = Color::fromString($notation);

        static::assertSame($red, $color->red);
        static::assertSame($green, $color->green);
        static::assertSame($blue, $color->blue);
        static::assertSame($alpha, $color->alpha);
    }

    /**
     * @return iterable<string, array{string, int, int, int, int}>
     */
    public static function supportedNotations(): iterable
    {
        yield 'six digits' => ['#ff0000', 255, 0, 0, 0];
        yield 'six digits without the hash' => ['ff0000', 255, 0, 0, 0];
        yield 'six digits in upper case' => ['#F5EF06', 245, 239, 6, 0];
        yield 'three digits' => ['#f00', 255, 0, 0, 0];
        yield 'three digits mixing channels' => ['#1a2', 17, 170, 34, 0];
        yield 'eight digits, fully opaque' => ['#ff0000ff', 255, 0, 0, 0];
        yield 'eight digits, half transparent' => ['#ff000080', 255, 0, 0, 63];
        yield 'eight digits, fully transparent' => ['#ff000000', 255, 0, 0, 127];
        yield 'four digits' => ['#f00c', 255, 0, 0, 25];
        yield 'the transparent keyword' => ['transparent', 0, 0, 0, 127];
        // The configuration documents colour names: ->example(['red', '#f5ef06']).
        yield 'a colour name' => ['red', 255, 0, 0, 0];
        yield 'a colour name in upper case' => ['WHITE', 255, 255, 255, 0];
        yield 'a colour name with surrounding spaces' => [' rebeccapurple ', 102, 51, 153, 0];
        yield 'the CSS grey, not the X11 one' => ['grey', 128, 128, 128, 0];
    }

    #[Test]
    #[DataProvider('unsupportedNotations')]
    public function itRejectsWhatItCannotResolve(string $notation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(sprintf('The color "%s" is not supported.', $notation));

        Color::fromString($notation);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unsupportedNotations(): iterable
    {
        yield 'an unknown name' => ['not-a-color'];
        yield 'a functional notation' => ['rgb(255, 0, 0)'];
        yield 'an empty string' => [''];
        yield 'too few digits' => ['#ff'];
        yield 'too many digits' => ['#ff0000ff00'];
        yield 'an odd digit count' => ['#ff000'];
        yield 'a non hexadecimal digit' => ['#ff00zz'];
    }
}
