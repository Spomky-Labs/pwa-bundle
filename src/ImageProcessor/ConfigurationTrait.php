<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use function assert;
use function is_int;
use function is_string;

/**
 * The plumbing the bundled processors share.
 *
 * @internal
 */
trait ConfigurationTrait
{
    /**
     * @return array{width: int, height: int}
     */
    abstract public function getSizes(string $image): array;

    /**
     * Whether the source is an SVG document, which the two processors decline for reasons of their own: GD cannot
     * rasterize one at all, and ImageMagick needs a delegate that is not always built in.
     */
    private function isSvg(string $image): bool
    {
        return str_contains(mb_strtolower(mb_substr($image, 0, 1024, '8bit')), '<svg');
    }

    private function getConfiguration(
        string $image,
        null|int $width,
        null|int $height,
        null|string $format,
        null|Configuration $configuration
    ): Configuration {
        if ($configuration !== null) {
            return $configuration;
        }
        trigger_deprecation(
            'spomky-labs/pwa-bundle',
            '1.2.0',
            'The "format", "width" and "height" parameters are deprecated and will be removed in 2.0.0. Please use "configuration" instead.'
        );

        if ($width === null && $height === null) {
            ['width' => $width, 'height' => $height] = $this->getSizes($image);
        }
        assert(is_int($width) && $width >= 1);
        assert(is_int($height) && $height >= 1);
        assert(is_string($format));

        return Configuration::create($width, $height, $format);
    }
}
