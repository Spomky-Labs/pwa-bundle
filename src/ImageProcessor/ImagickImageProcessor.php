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
            $width = $mainImage->getImageWidth();
            $height = $mainImage->getImageHeight();
            $newWidth = (int) ($width * $configuration->imageScale / 100);
            $newHeight = (int) ($height * $configuration->imageScale / 100);
            $widthCenter = (int) (-($width - $newWidth) / 2);
            $heightCenter = (int) (-($height - $newHeight) / 2);

            $mainImage->scaleImage($newWidth, $newHeight);
            $mainImage->extentImage($width, $height, $widthCenter, $heightCenter);
        }

        if ($configuration->width === $configuration->height) {
            $mainImage->scaleImage($configuration->width, $configuration->height);

            return $mainImage;
        }

        $mainImage->scaleImage(
            min($configuration->width, $configuration->height),
            min($configuration->width, $configuration->height)
        );
        $mainImage->extentImage(
            $configuration->width,
            $configuration->height,
            -($configuration->width - min($configuration->width, $configuration->height)) / 2,
            -($configuration->height - min($configuration->width, $configuration->height)) / 2
        );

        return $mainImage;
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
}
