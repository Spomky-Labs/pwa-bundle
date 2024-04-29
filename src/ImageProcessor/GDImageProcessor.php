<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use function assert;

final readonly class GDImageProcessor implements ImageProcessorInterface
{
    public function process(string $image, ?int $width, ?int $height, ?string $format): string
    {
        if ($width === null && $height === null) {
            ['width' => $width, 'height' => $height] = $this->getSizes($image);
        }
        $image = imagecreatefromstring($image);
        assert($image !== false);
        imagealphablending($image, true);
        if ($width !== null && $height !== null) {
            if ($width === $height) {
                $image = imagescale($image, $width, $height);
            } else {
                $newImage = imagecreatetruecolor($width, $height);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
                $image = $newImage;
            }
        }
        ob_start();
        imagesavealpha($image, true);
        imagepng($image);
        return ob_get_clean();
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getSizes(string $image): array
    {
        $image = imagecreatefromstring($image);
        return [
            'width' => imagesx($image),
            'height' => imagesy($image),
        ];
    }
}
