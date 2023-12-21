<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessor;

class DummyImageProcessor implements ImageProcessor
{
    public function process(string $image, ?int $width, ?int $height, ?string $format): string
    {
        return json_encode([
            'width' => $width,
            'height' => $height,
            'format' => $format,
        ]);
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getSizes(string $image): array
    {
        return [
            'width' => 1024,
            'height' => 1920,
        ];
    }
}
