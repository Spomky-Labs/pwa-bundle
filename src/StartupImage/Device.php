<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

use function assert;
use function sprintf;

/**
 * One iOS device, described the way its media query describes it: "device-width" and "device-height" always
 * measure the screen in its natural, portrait orientation, in CSS pixels. The landscape image of a device is
 * therefore the very same numbers, swapped.
 */
final readonly class Device
{
    public function __construct(
        public string $name,
        public int $width,
        public int $height,
        public int $pixelRatio,
    ) {
    }

    public static function create(string $name, int $width, int $height, int $pixelRatio): self
    {
        return new self($name, $width, $height, $pixelRatio);
    }

    /**
     * @return int<1, max>
     */
    public function pixelWidth(Orientation $orientation): int
    {
        $side = $orientation === Orientation::PORTRAIT ? $this->width : $this->height;
        $pixels = $side * $this->pixelRatio;
        assert($pixels >= 1);

        return $pixels;
    }

    /**
     * @return int<1, max>
     */
    public function pixelHeight(Orientation $orientation): int
    {
        $side = $orientation === Orientation::PORTRAIT ? $this->height : $this->width;
        $pixels = $side * $this->pixelRatio;
        assert($pixels >= 1);

        return $pixels;
    }

    public function mediaQuery(Orientation $orientation): string
    {
        return sprintf(
            '(device-width: %dpx) and (device-height: %dpx) and (-webkit-device-pixel-ratio: %d) and (orientation: %s)',
            $this->width,
            $this->height,
            $this->pixelRatio,
            $orientation->value,
        );
    }
}
