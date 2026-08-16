<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ImageProcessor;

use function function_exists;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use function restore_error_handler;
use function set_error_handler;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\GDImageProcessor;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageFormat;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use function sprintf;

/**
 * @internal
 */
#[RequiresPhpExtension('gd')]
final class GDImageProcessorTest extends ImageProcessorTestCase
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
        foreach ([
            'bmp' => 'imagebmp',
            'webp' => 'imagewebp',
            'avif' => 'imageavif',
        ] as $format => $encoder) {
            if (function_exists($encoder)) {
                yield $format => [Configuration::create(64, 64, $format)];
            }
        }
        yield 'a hexadecimal background' => [Configuration::create(64, 64, 'png', '#f5ef06')];
        yield 'a named background' => [Configuration::create(64, 64, 'png', 'red')];
        yield 'a rounded background' => [Configuration::create(64, 64, 'png', 'red', 25)];
        yield 'a scaled image' => [Configuration::create(64, 64, 'png', null, null, 50)];
        yield 'a monochrome icon' => [Configuration::create(64, 64, 'png', null, null, null, true)];
        yield 'a non square target' => [Configuration::create(310, 150, 'png')];
    }

    #[Test]
    public function itNamesTheAlternativeWhenHandedAnSvg(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Use the Imagick image processor');

        $this->processor()
            ->getSizes('<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"></svg>');
    }

    #[Test]
    public function itListsTheFormatsItWritesWhenHandedAnotherOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Supported formats are: png, jpeg, gif, ico, bmp, webp, avif.');

        $this->processor()
            ->process(self::sourceImage(8, 8), null, null, null, Configuration::create(8, 8, 'tiff'));
    }

    protected function processor(): ImageProcessorInterface
    {
        return new GDImageProcessor();
    }

    protected function canWrite(string $format): bool
    {
        return match (ImageFormat::normalize($format)) {
            'bmp' => function_exists('imagebmp'),
            'webp' => function_exists('imagewebp'),
            'avif' => function_exists('imageavif'),
            default => true,
        };
    }
}
