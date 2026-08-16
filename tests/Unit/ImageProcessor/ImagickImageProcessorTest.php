<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests\Unit\ImageProcessor;

use Imagick;
use ImagickPixel;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use SpomkyLabs\PwaBundle\ImageProcessor\Configuration;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageFormat;
use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use SpomkyLabs\PwaBundle\ImageProcessor\ImagickImageProcessor;
use Throwable;

/**
 * @internal
 */
#[RequiresPhpExtension('gd')]
#[RequiresPhpExtension('imagick')]
final class ImagickImageProcessorTest extends ImageProcessorTestCase
{
    /**
     * An SVG source is the one thing the two processors cannot answer alike: GD never rasterizes one, while
     * ImageMagick does whenever it was built with a delegate for it. What is owed either way is a message stating
     * which of the two situations the caller is in.
     */
    #[Test]
    public function itReadsAnSvgSourceOrNamesTheMissingDelegate(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="8"><rect width="16" height="8" fill="red"/></svg>';

        try {
            $sizes = $this->processor()
                ->getSizes($svg);
        } catch (InvalidArgumentException $exception) {
            static::assertStringContainsString('SVG delegate', $exception->getMessage());
            return;
        }

        static::assertSame(
            [
                'width' => 16,
                'height' => 8,
            ],
            $sizes
        );
    }

    /**
     * ImageMagick writes far more than the formats the two processors have in common, and that stays true.
     */
    #[Test]
    public function itKeepsWritingTheFormatsOnlyImageMagickKnows(): void
    {
        $result = $this->processor()
            ->process(self::sourceImage(32, 32), null, null, null, Configuration::create(32, 32, 'tiff'));

        static::assertNotSame('', $result);
    }

    /**
     * ImageMagick knows colour names of its own, beyond the CSS ones the two processors share.
     */
    #[Test]
    public function itKeepsResolvingTheColorNamesOnlyImageMagickKnows(): void
    {
        $result = $this->processor()
            ->process(
                self::sourceImage(8, 8, transparent: true),
                null,
                null,
                null,
                Configuration::create(64, 64, 'png', 'gray10')
            );

        static::assertSame(0, self::alphaAt($result, 32, 32));
    }

    protected function processor(): ImageProcessorInterface
    {
        return new ImagickImageProcessor();
    }

    /**
     * Imagick::queryFormats() lists the formats ImageMagick registered, including those whose delegate is declared but
     * unusable, so the encoder is the only reliable answer.
     */
    protected function canWrite(string $format): bool
    {
        try {
            $image = new Imagick();
            $image->newImage(1, 1, new ImagickPixel('red'));
            $image->setImageFormat(ImageFormat::normalize($format));
            $image->getImageBlob();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
