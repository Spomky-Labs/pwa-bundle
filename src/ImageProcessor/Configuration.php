<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use InvalidArgumentException;
use Stringable;
use function sprintf;

final readonly class Configuration implements Stringable
{
    public function __construct(
        public int $width,
        public int $height,
        public string $format,
        public null|string $backgroundColor = null,
        public null|int $borderRadius = null,
        public null|int $imageScale = null,
        public bool $monochrome = false,
        public string $svgColor = '#000',
    ) {
        if ($this->borderRadius !== null && $this->backgroundColor === null) {
            throw new InvalidArgumentException('The background color must be set when the border radius is set');
        }
    }

    public function __toString(): string
    {
        return sprintf(
            '%d%d%s%s%d%d%s%s',
            $this->width,
            $this->height,
            $this->format,
            $this->backgroundColor ?? '',
            $this->borderRadius ?? 0,
            $this->imageScale ?? 0,
            $this->monochrome ? '1' : '0',
            $this->svgColor,
        );
    }

    public static function create(
        int $width,
        int $height,
        string $format,
        null|string $backgroundColor = null,
        null|int $borderRadius = null,
        null|int $imageScale = null,
        bool $monochrome = false,
        string $svgColor = '#000',
    ): self {
        return new self($width, $height, $format, $backgroundColor, $borderRadius, $imageScale, $monochrome, $svgColor);
    }
}
