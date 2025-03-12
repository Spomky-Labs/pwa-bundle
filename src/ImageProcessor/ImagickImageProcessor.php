<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use Imagick;
use ImagickDraw;
use ImagickPixel;

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
        $background->setImageFormat($configuration->format);

        return $background->getImageBlob();
    }

    public function getSizes(string $image): array
    {
        $imagick = new Imagick();
        $imagick->readImageBlob($image);
        return [
            'width' => $imagick->getImageWidth(),
            'height' => $imagick->getImageHeight(),
        ];
    }

    private function createMainImage(string $image, Configuration $configuration): Imagick
    {
        $mainImage = new Imagick();
        $mainImage->setBackgroundColor(new ImagickPixel('transparent'));
        $mainImage->readImageBlob($image);
        $mainImage->setImageBackgroundColor(new ImagickPixel('transparent'));

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
                new ImagickPixel($configuration->backgroundColor)
            );
            return $background;
        }

        $rectangle = new ImagickDraw();
        $rectangle->setFillColor(new ImagickPixel($configuration->backgroundColor));
        $rectangle->roundRectangle(
            0,
            0,
            $configuration->width,
            $configuration->height,
            (int) ($configuration->borderRadius * $configuration->width / 100),
            (int) ($configuration->borderRadius * $configuration->height / 100)
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
