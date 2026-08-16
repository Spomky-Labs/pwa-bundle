<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\StartupImage;

use SpomkyLabs\PwaBundle\Dto\StartupImageTheme;

/**
 * One image to produce: a device, an orientation and the color scheme they are declined for.
 */
final readonly class StartupImageDefinition
{
    /**
     * @param int<1, max> $width
     * @param int<1, max> $height
     */
    private function __construct(
        public StartupImageTheme $theme,
        public ColorScheme $colorScheme,
        public Device $device,
        public Orientation $orientation,
        public int $width,
        public int $height,
    ) {
    }

    public static function create(
        StartupImageTheme $theme,
        ColorScheme $colorScheme,
        Device $device,
        Orientation $orientation,
    ): self {
        return new self(
            $theme,
            $colorScheme,
            $device,
            $orientation,
            $device->pixelWidth($orientation),
            $device->pixelHeight($orientation),
        );
    }

    public function mediaQuery(): string
    {
        return $this->device->mediaQuery($this->orientation);
    }
}
