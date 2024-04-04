<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use Imagick;
use ImagickPixel;

final readonly class ImagickImageProcessor implements ImageProcessorInterface
{
    public function __construct(
        private int $filters = Imagick::FILTER_LANCZOS2,
        private float $blur = 1,
    ) {
    }

    public function process(string $image, ?int $width, ?int $height, ?string $format): string
    {
        if ($width === null && $height === null) {
            ['width' => $width, 'height' => $height] = $this->getSizes($image);
        }
        $imagick = new Imagick();
        $imagick->readImageBlob($image);
        if ($width !== null && $height !== null) {
            $imagick->resizeImage($width, $height, $this->filters, $this->blur, true);
        }
        $imagick->setImageBackgroundColor(new ImagickPixel('transparent'));
        if ($format !== null) {
            $imagick->setImageFormat($format);
        }
        return $imagick->getImageBlob();
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
}
