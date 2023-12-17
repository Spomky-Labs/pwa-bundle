<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

final readonly class GDImageProcessor implements ImageProcessor
{
    public function process(string $image, ?int $width, ?int $height, ?string $format): string
    {
        if ($width === null && $height === null) {
            ['width' => $width, 'height' => $height] = $this->getSizes($image);
        }
        $image = imagecreatefromstring($image);
        imagealphablending($image, true);
        $image = imagescale($image, $width, $height);
        ob_start();
        imagesavealpha($image, true);
        imagepng($image);
        return ob_get_clean();
    }

    public function getSizes(string $image): array
    {
        $image = imagecreatefromstring($image);
        return [
            'width' => imagesx($image),
            'height' => imagesy($image),
        ];
    }
}
