<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use InvalidArgumentException;

final readonly class Configuration
{
    public function __construct(
        public int $width,
        public int $height,
        public string $format,
        public null|string $backgroundColor = null,
        public null|int $borderRadius = null,
        public null|int $imageScale = null,
    ) {
        if ($this->borderRadius !== null && $this->backgroundColor === null) {
            throw new InvalidArgumentException('The background color must be set when the border radius is set');
        }
    }

    public static function create(
        int $width,
        int $height,
        string $format,
        null|string $backgroundColor = null,
        null|int $borderRadius = null,
        null|int $imageScale = null,
    ): self {
        return new self($width, $height, $format, $backgroundColor, $borderRadius, $imageScale);
    }
}
