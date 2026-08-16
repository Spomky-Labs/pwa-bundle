<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ImageProcessor;

use function assert;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use function sprintf;

/**
 * The behaviour both bundled processors owe their callers.
 *
 * The two used to answer the same configuration quite differently: GD knew five formats where Imagick knew every one
 * ImageMagick writes, resolved "red" to a near black colour, ignored the monochrome flag, and cropped whatever source
 * was less square than its target instead of fitting it inside. Every expectation below is checked against both, so
 * neither drifts away from the other again.
 *
 * @internal
 */
abstract class ImageProcessorTestCase extends TestCase
{
    #[Test]
    public function itReadsTheSizeOfTheSource(): void
    {
        static::assertSame(
            [
                'width' => 128,
                'height' => 64,
            ],
            $this->processor()
                ->getSizes(self::sourceImage(128, 64))
        );
    }

    #[Test]
    #[DataProvider('formats')]
    public function itWritesTheSupportedFormats(string $format, string $magic, int $offset = 0): void
    {
        // The codecs behind WebP and AVIF are build options of either library, and a build without them is not a
        // regression of the bundle.
        if (! $this->canWrite($format)) {
            static::markTestSkipped(sprintf('This build cannot write the "%s" format.', $format));
        }

        $result = $this->processor()
            ->process(self::sourceImage(32, 32), null, null, null, Configuration::create(32, 32, $format));

        static::assertSame($magic, bin2hex(mb_substr($result, $offset, mb_strlen($magic, '8bit') / 2, '8bit')));
    }

    /**
     * @return iterable<string, array{string, string}|array{string, string, int}>
     */
    public static function formats(): iterable
    {
        yield 'png' => ['png', '89504e47'];
        yield 'jpeg' => ['jpeg', 'ffd8ff'];
        // The format also comes from the extension of the source asset, which spells JPEG the short way.
        yield 'jpg' => ['jpg', 'ffd8ff'];
        yield 'gif' => ['gif', '474946'];
        yield 'ico' => ['ico', '00000100'];
        // The silhouette of the Safari pinned tab is handed to potrace as a BMP.
        yield 'bmp' => ['bmp', '424d'];
        yield 'webp' => ['webp', '52494646'];
        // "ftypavif", behind the size of the first ISO base media file format box.
        yield 'avif' => ['avif', '6674797061766966', 4];
        // The configuration takes the format as a free scalar, in whatever case it was written.
        yield 'PNG' => ['PNG', '89504e47'];
        yield 'JPEG' => ['JPEG', 'ffd8ff'];
    }

    #[Test]
    public function itRejectsAFormatItCannotWrite(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('not-a-format');

        $this->processor()
            ->process(self::sourceImage(32, 32), null, null, null, Configuration::create(32, 32, 'not-a-format'));
    }

    #[Test]
    #[DataProvider('backgroundColors')]
    public function itPaintsTheConfiguredBackgroundColor(string $notation, int $expected): void
    {
        // A fully transparent source lets the background show through.
        $result = $this->processor()
            ->process(
                self::sourceImage(8, 8, transparent: true),
                null,
                null,
                null,
                Configuration::create(64, 64, 'png', $notation)
            );

        static::assertSame($expected, self::pixelAt($result, 32, 32));
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function backgroundColors(): iterable
    {
        // The configuration documents both notations: ->example(['red', '#f5ef06']).
        yield 'a colour name' => ['red', 0x00FF0000];
        yield 'the name the safari pinned tab uses' => ['white', 0x00FFFFFF];
        yield 'six hexadecimal digits' => ['#f5ef06', 0x00F5EF06];
        yield 'six hexadecimal digits without the hash' => ['f5ef06', 0x00F5EF06];
        yield 'three hexadecimal digits' => ['#f00', 0x00FF0000];
        yield 'eight hexadecimal digits' => ['#ff000080', 0x3FFF0000];
        yield 'rgb()' => ['rgb(255, 0, 0)', 0x00FF0000];
        yield 'hsl()' => ['hsl(0, 100%, 50%)', 0x00FF0000];
    }

    #[Test]
    public function itRejectsABackgroundColorItCannotResolve(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('not-a-color');

        $this->processor()
            ->process(
                self::sourceImage(8, 8),
                null,
                null,
                null,
                Configuration::create(64, 64, 'png', 'not-a-color')
            );
    }

    /**
     * A source wider than its target has to be fitted inside it, not cropped by it.
     */
    #[Test]
    public function itFitsTheWholeSourceInsideTheTarget(): void
    {
        $result = $this->processor()
            ->process(self::sourceImage(1024, 256), null, null, null, Configuration::create(512, 512, 'png'));

        // 1024x256 inside 512x512 leaves a 512x128 band, centred: rows 192 to 320.
        static::assertSame(127, self::alphaAt($result, 256, 10), 'the band above the image should be transparent');
        static::assertSame(0, self::alphaAt($result, 256, 256), 'the image itself should be opaque');
        static::assertSame(127, self::alphaAt($result, 256, 500), 'the band below the image should be transparent');
        static::assertSame(0, self::alphaAt($result, 4, 256), 'the image should span the whole width');
    }

    #[Test]
    public function itTurnsAMonochromeIconToGrey(): void
    {
        $result = $this->processor()
            ->process(
                self::sourceImage(64, 64),
                null,
                null,
                null,
                Configuration::create(64, 64, 'png', null, null, null, true)
            );

        $pixel = self::pixelAt($result, 32, 32);
        $red = ($pixel >> 16) & 0xFF;
        $green = ($pixel >> 8) & 0xFF;
        $blue = $pixel & 0xFF;

        static::assertSame($red, $green);
        static::assertSame($green, $blue);
        // The Rec. 709 luminance of the rgb(0, 128, 255) source, which is what ImageMagick applies.
        static::assertEqualsWithDelta(110, $red, 2.0);
    }

    #[Test]
    public function itRoundsTheCornersOfTheBackground(): void
    {
        $result = $this->processor()
            ->process(
                self::sourceImage(8, 8, transparent: true),
                null,
                null,
                null,
                Configuration::create(128, 128, 'png', 'red', 25)
            );

        static::assertSame(127, self::alphaAt($result, 0, 0), 'the corner should be cut out');
        static::assertSame(0, self::alphaAt($result, 64, 0), 'the middle of the edge should be kept');

        $antialiased = 0;
        for ($x = 0; $x < 128; $x++) {
            for ($y = 0; $y < 128; $y++) {
                $alpha = self::alphaAt($result, $x, $y);
                if ($alpha > 0 && $alpha < 127) {
                    $antialiased++;
                }
            }
        }
        static::assertGreaterThan(100, $antialiased, 'the rounded edge should be antialiased');
    }

    #[Test]
    public function itRejectsASourceItCannotRead(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The image cannot be read');

        $this->processor()
            ->getSizes('not an image at all');
    }

    abstract protected function processor(): ImageProcessorInterface;

    /**
     * Whether the library behind the processor was built with the codec of that format.
     */
    abstract protected function canWrite(string $format): bool;

    /**
     * A solid rgb(0, 128, 255) image, opaque unless asked otherwise.
     */
    protected static function sourceImage(int $width, int $height, bool $transparent = false): string
    {
        $image = imagecreatetruecolor($width, $height);
        assert($image !== false);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $color = imagecolorallocatealpha($image, 0, 128, 255, $transparent ? 127 : 0);
        assert($color !== false);
        imagefill($image, 0, 0, $color);

        ob_start();
        imagepng($image);
        $result = ob_get_clean();
        assert($result !== false);

        return $result;
    }

    /**
     * @return int The pixel as GD packs it: alpha on the high byte, then red, green and blue.
     */
    protected static function pixelAt(string $png, int $x, int $y): int
    {
        $image = imagecreatefromstring($png);
        assert($image !== false, sprintf('The %d bytes long result is not a readable image.', mb_strlen($png, '8bit')));
        imagealphablending($image, false);

        return imagecolorat($image, $x, $y);
    }

    /**
     * @return int 0 is fully opaque, 127 fully transparent.
     */
    protected static function alphaAt(string $png, int $x, int $y): int
    {
        return (self::pixelAt($png, $x, $y) >> 24) & 0x7F;
    }
}
