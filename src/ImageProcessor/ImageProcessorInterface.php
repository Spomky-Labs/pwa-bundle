<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

interface ImageProcessorInterface
{
    public function process(
        string $image,
        ?int $width,
        ?int $height,
        ?string $format,
        null|Configuration $configuration = null
    ): string;

    /**
     * @return array{width: int, height: int}
     */
    public function getSizes(string $image): array;
}
