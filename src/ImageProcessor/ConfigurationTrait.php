<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use function assert;
use function is_int;
use function is_string;

/**
 * @internal
 */
trait ConfigurationTrait
{
    /**
     * @return array{width: int, height: int}
     */
    abstract public function getSizes(string $image): array;

    private function getConfiguration(
        string $image,
        null|int $width,
        null|int $height,
        null|string $format,
        null|Configuration $configuration
    ): Configuration {
        if (($width !== null || $height !== null || $format !== null) && $configuration === null) {
            trigger_deprecation(
                'spomky-labs/pwa-bundle',
                '1.2.0',
                'The "format", "width" and "height" parameters are deprecated and will be removed in 2.0.0. Please use "configuration" instead.'
            );
        }
        if ($configuration !== null) {
            return $configuration;
        }

        if ($width === null && $height === null) {
            ['width' => $width, 'height' => $height] = $this->getSizes($image);
        }
        assert(is_int($width));
        assert(is_int($height));
        assert(is_string($format));

        return Configuration::create($width, $height, $format);
    }
}
