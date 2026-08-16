<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use Imagick;
use ImagickDraw;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;
use InvalidArgumentException;
use function sprintf;

final readonly class ImagickImageProcessor implements ImageProcessorInterface
{
    use ConfigurationTrait;

    public function process(
        string $image,
        null|int $width,
        null|int $height,
        null|string $format,
        null|Configuration $configuration = null
    ): string {
        $configuration = $this->getConfiguration($image, $width, $height, $format, $configuration);
        $mainImage = $this->createMainImage($image, $configuration);
        $background = $this->createBackground($configuration);
        $background->compositeImage($mainImage, Imagick::COMPOSITE_OVER, 0, 0);

        return $this->encode($background, ImageFormat::normalize($configuration->format));
    }

    public function getSizes(string $image): array
    {
        $imagick = $this->read($image);

        return [
            'width' => $imagick->getImageWidth(),
            'height' => $imagick->getImageHeight(),
        ];
    }

    /**
     * A format ImageMagick was not built for is only reported when the blob is asked for, not when the format is set,
     * so the whole encoding step is guarded. The reason ImageMagick gave is kept as the previous exception.
     */
    private function encode(Imagick $image, string $format): string
    {
        try {
            $image->setImageFormat($format === 'png' ? 'png32' : $format);
            $image->setImageCompressionQuality(ImageFormat::QUALITY);

            return $image->getImageBlob();
        } catch (ImagickException $exception) {
            throw new InvalidArgumentException(
                sprintf('The "%s" format is not supported by the Imagick image processor.', $format),
                0,
                $exception
            );
        }
    }

    /**
     * ImagickException carries the internal ImageMagick wording, which says nothing of what to do about it; the
     * failure is restated here, and the two processors report an unreadable source the same way.
     */
    private function read(string $image): Imagick
    {
        $imagick = new Imagick();
        $imagick->setBackgroundColor(new ImagickPixel('transparent'));
        try {
            $imagick->readImageBlob($image);
        } catch (ImagickException $exception) {
            if ($this->isSvg($image)) {
                throw new InvalidArgumentException(
                    'The Imagick image processor cannot read this SVG image. ImageMagick has to be built with an SVG delegate, such as librsvg.',
                    0,
                    $exception
                );
            }

            throw new InvalidArgumentException(
                'The image cannot be read: its format is not recognized by ImageMagick.',
                0,
                $exception
            );
        }
        $imagick->setImageBackgroundColor(new ImagickPixel('transparent'));

        return $imagick;
    }

    /**
     * Resolves the colour the way the GD processor does, so both accept the very same notations. ImageMagick knows
     * colour names of its own beyond the CSS ones, which keep working through the fallback.
     */
    private function pixel(string $color): ImagickPixel
    {
        try {
            return new ImagickPixel(Color::fromString($color)->toHex());
        } catch (InvalidArgumentException $exception) {
            try {
                return new ImagickPixel($color);
            } catch (ImagickPixelException) {
                throw $exception;
            }
        }
    }

    private function createMainImage(string $image, Configuration $configuration): Imagick
    {
        $mainImage = $this->read($image);

        if ($configuration->imageScale !== null) {
            $mainImage = $this->resizeImageWithScale($mainImage, $configuration->imageScale);
        }

        // Resize image with new size to best fit the configuration
        $mainImage->scaleImage($configuration->width, $configuration->height, true);

        $background = new Imagick();
        $background->newImage($configuration->width, $configuration->height, new ImagickPixel('transparent'));
        $background->compositeImage(
            $mainImage,
            Imagick::COMPOSITE_OVER,
            (int) (($configuration->width - $mainImage->getImageWidth()) / 2),
            (int) (($configuration->height - $mainImage->getImageHeight()) / 2)
        );
        if ($configuration->monochrome) {
            $background->setImageType(Imagick::IMGTYPE_GRAYSCALEMATTE);
        }

        return $background;
    }

    private function createBackground(Configuration $configuration): Imagick
    {
        if ($configuration->backgroundColor === null) {
            $background = new Imagick();
            $background->newImage($configuration->width, $configuration->height, new ImagickPixel('transparent'));
            return $background;
        }

        if ($configuration->borderRadius === null) {
            $background = new Imagick();
            $background->newImage(
                $configuration->width,
                $configuration->height,
                $this->pixel($configuration->backgroundColor)
            );
            return $background;
        }

        $rectangle = new ImagickDraw();
        $rectangle->setFillColor($this->pixel($configuration->backgroundColor));
        // The last pixel of each axis is at width - 1: spanning the rectangle to width made the shape one pixel wider
        // and one pixel taller than the canvas, and the overflow was clipped, leaving the bottom right corner visibly
        // less rounded than the top left one.
        $rectangle->roundRectangle(
            0,
            0,
            $configuration->width - 1,
            $configuration->height - 1,
            max(1, (int) round($configuration->borderRadius * $configuration->width / 100)),
            max(1, (int) round($configuration->borderRadius * $configuration->height / 100))
        );
        $background = new Imagick();
        $background->newImage($configuration->width, $configuration->height, new ImagickPixel('transparent'));
        $background->drawImage($rectangle);

        return $background;
    }

    private function resizeImageWithScale(Imagick $image, float|int $imageScale): Imagick
    {
        $imageWidth = $image->getImageWidth();
        $imageHeight = $image->getImageHeight();
        $newWidth = (int) ($imageWidth * $imageScale / 100);
        $newHeight = (int) ($imageHeight * $imageScale / 100);
        $image->scaleImage($newWidth, $newHeight, true);

        $mainImage = new Imagick();
        $x = (int) (($imageWidth - $newWidth) / 2);
        $y = (int) (($imageHeight - $newHeight) / 2);
        $mainImage->newImage($imageWidth, $imageHeight, new ImagickPixel('transparent'));
        $mainImage->setBackgroundColor(new ImagickPixel('transparent'));
        $mainImage->setImageBackgroundColor(new ImagickPixel('transparent'));
        $mainImage->compositeImage($image, Imagick::COMPOSITE_OVER, $x, $y);
        $mainImage->setImageFormat($image->getImageFormat());

        return $mainImage;
    }
}
