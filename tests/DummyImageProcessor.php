<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Tests;

use SpomkyLabs\PwaBundle\ImageProcessor\ImageProcessorInterface;
use function assert;

/**
 * @internal
 */
class DummyImageProcessor implements ImageProcessorInterface
{
    public function process(string $image, ?int $width, ?int $height, ?string $format): string
    {
        $json = json_encode([
            'width' => $width,
            'height' => $height,
            'format' => $format,
        ]);
        assert($json !== false);

        return $json;
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
