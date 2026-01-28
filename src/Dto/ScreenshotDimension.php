<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Dto;

use function assert;
use function preg_match;
use function sprintf;

final readonly class ScreenshotDimension
{
    private function __construct(
        private null|ScreenshotSize $profile,
        private null|int $width,
        private null|int $height,
        private string $label,
    ) {
    }

    public static function fromProfile(ScreenshotSize $profile): self
    {
        return new self($profile, null, null, $profile->getLabel());
    }

    public static function fromDimensions(int $width, int $height): self
    {
        $label = sprintf('%d×%d', $width, $height);
        return new self(null, $width, $height, $label);
    }

    /**
     * Parse a size string that can be either:
     * - A profile name (e.g., "fhd", "iphone-14")
     * - Explicit dimensions (e.g., "1920x1080", "1920×1080")
     */
    public static function fromString(string $value): ?self
    {
        // Try as profile first
        $profile = ScreenshotSize::fromString($value);
        if ($profile !== null) {
            return self::fromProfile($profile);
        }

        // Try to parse as dimensions (e.g., "1920x1080" or "1920×1080")
        if (preg_match('/^(\d+)[x×](\d+)$/i', $value, $matches) === 1) {
            $width = (int) $matches[1];
            $height = (int) $matches[2];
            if ($width > 0 && $height > 0) {
                return self::fromDimensions($width, $height);
            }
        }

        return null;
    }

    /**
     * Expand a size string into one or more dimensions, supporting orientation selectors:
     * - "ipad" → Default orientation from profile
     * - "ipad/L" → Landscape only (width > height)
     * - "ipad/P" → Portrait only (width < height)
     * - "ipad/LP" → Both landscape AND portrait
     * - "1920x1080/P" → Portrait version (1080×1920)
     *
     * @return array<self>
     */
    public static function expandFromString(string $value): array
    {
        // Check for orientation suffix: /L, /P, or /LP
        if (preg_match('/^(.+)\/(L|P|LP)$/i', $value, $matches) === 1) {
            $baseValue = $matches[1];
            $orientation = strtoupper($matches[2]);

            // Parse the base profile or dimensions
            $baseDimension = self::fromString($baseValue);
            if ($baseDimension === null) {
                return [];
            }

            $dims = $baseDimension->getDimensions();
            $width = $dims['width'];
            $height = $dims['height'];

            // Ensure we have landscape and portrait versions
            $landscapeWidth = max($width, $height);
            $landscapeHeight = min($width, $height);
            $portraitWidth = $landscapeHeight;
            $portraitHeight = $landscapeWidth;

            $result = [];

            if ($orientation === 'L') {
                // Landscape only
                $label = $baseDimension->getLabel() . ' (Landscape)';
                $result[] = new self(null, $landscapeWidth, $landscapeHeight, $label);
            } elseif ($orientation === 'P') {
                // Portrait only
                $label = $baseDimension->getLabel() . ' (Portrait)';
                $result[] = new self(null, $portraitWidth, $portraitHeight, $label);
            } else {
                // LP - Both orientations
                $labelBase = $baseDimension->getLabel();
                $result[] = new self(null, $portraitWidth, $portraitHeight, $labelBase . ' (Portrait)');
                $result[] = new self(null, $landscapeWidth, $landscapeHeight, $labelBase . ' (Landscape)');
            }

            return $result;
        }

        // No orientation suffix - use default
        $dimension = self::fromString($value);
        return $dimension !== null ? [$dimension] : [];
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getDimensions(): array
    {
        if ($this->profile !== null) {
            return $this->profile->getDimensions();
        }

        assert($this->width !== null && $this->height !== null);

        return [
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public function getFormFactor(): string
    {
        if ($this->profile !== null) {
            return $this->profile->getFormFactor();
        }

        return $this->width > $this->height ? 'wide' : 'narrow';
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isProfile(): bool
    {
        return $this->profile !== null;
    }

    public function getProfileName(): ?string
    {
        return $this->profile?->value;
    }
}
