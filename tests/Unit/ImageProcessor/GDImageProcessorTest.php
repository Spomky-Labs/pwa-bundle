<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ImageProcessor;

use function assert;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function restore_error_handler;
use function set_error_handler;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use function sprintf;

/**
 * @internal
 */
#[RequiresPhpExtension('gd')]
final class GDImageProcessorTest extends TestCase
{
    /**
     * PHP 8.5 deprecated imagedestroy(), and hexdec() has been complaining about non hexadecimal characters since 7.4.
     * Both used to fire while generating perfectly valid icons, so the whole processing surface is replayed here with
     * every diagnostic turned into a failure.
     */
    #[Test]
    #[DataProvider('representativeConfigurations')]
    public function itProcessesImagesWithoutTriggeringAnyDiagnostic(Configuration $configuration): void
    {
        $processor = new GDImageProcessor();
        $source = self::sourceImage(256, 192);

        $diagnostics = [];
        set_error_handler(static function (int $level, string $message) use (&$diagnostics): bool {
            $diagnostics[] = sprintf('[%d] %s', $level, $message);
            return true;
        });

        try {
            $processor->getSizes($source);
            $result = $processor->process($source, null, null, null, $configuration);
        } finally {
            restore_error_handler();
        }

        static::assertSame([], $diagnostics);
        static::assertNotSame('', $result);
    }

    /**
     * @return iterable<string, array{Configuration}>
     */
    public static function representativeConfigurations(): iterable
    {
        yield 'png' => [Configuration::create(64, 64, 'png')];
        yield 'ico' => [Configuration::create(64, 64, 'ico')];
        yield 'jpeg' => [Configuration::create(64, 64, 'jpeg')];
        yield 'gif' => [Configuration::create(64, 64, 'gif')];
        yield 'a hexadecimal background' => [Configuration::create(64, 64, 'png', '#f5ef06')];
        yield 'a named background' => [Configuration::create(64, 64, 'png', 'red')];
        yield 'a rounded background' => [Configuration::create(64, 64, 'png', 'red', 25)];
        yield 'a scaled image' => [Configuration::create(64, 64, 'png', null, null, 50)];
        yield 'a non square target' => [Configuration::create(310, 150, 'png')];
    }

    /**
     * The background colour notations the configuration documents have to reach the generated icon untouched.
     */
    #[Test]
    #[DataProvider('backgroundColors')]
    public function itPaintsTheConfiguredBackgroundColor(string $notation, int $expected): void
    {
        $processor = new GDImageProcessor();
        // A fully transparent source lets the background show through.
        $result = $processor->process(
            self::sourceImage(8, 8, true),
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
        yield 'a colour name' => ['red', 0x00FF0000];
        yield 'the name used by the safari pinned tab' => ['white', 0x00FFFFFF];
        yield 'the hexadecimal notation of the documentation' => ['#f5ef06', 0x00F5EF06];
        yield 'the short hexadecimal notation' => ['#f00', 0x00FF0000];
        yield 'the hexadecimal notation without a hash' => ['f5ef06', 0x00F5EF06];
        yield 'a half transparent colour' => ['#ff000080', 0x3FFF0000];
    }

    #[Test]
    public function itRejectsABackgroundColorItCannotResolve(): void
    {
        $processor = new GDImageProcessor();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('The color "rgb(255, 0, 0)" is not supported.');

        $processor->process(
            self::sourceImage(8, 8),
            null,
            null,
            null,
            Configuration::create(64, 64, 'png', 'rgb(255, 0, 0)')
        );
    }

    #[Test]
    public function itCutsTheCornersOutOfARoundedBackground(): void
    {
        $processor = new GDImageProcessor();
        $result = $processor->process(
            self::sourceImage(8, 8, true),
            null,
            null,
            null,
            Configuration::create(64, 64, 'png', 'red', 25)
        );

        // Transparent on the corner, opaque halfway along the edge.
        static::assertSame(127, self::pixelAt($result, 0, 0) >> 24);
        static::assertSame(0x00FF0000, self::pixelAt($result, 32, 0));
    }

    private static function sourceImage(int $width, int $height, bool $transparent = false): string
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
    private static function pixelAt(string $png, int $x, int $y): int
    {
        $image = imagecreatefromstring($png);
        assert($image !== false);
        imagealphablending($image, false);

        return imagecolorat($image, $x, $y);
    }
}
