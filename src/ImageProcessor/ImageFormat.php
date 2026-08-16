<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ImageProcessor;

use function mb_strtolower;

/**
 * The output format conventions both bundled processors follow.
 *
 * @internal
 */
final class ImageFormat
{
    /**
     * The quality of the lossy encoders.
     *
     * Both processors used to leave it to their extension, which meant the very same configuration produced
     * noticeably different files: GD fell back on its default of 75, while ImageMagick picked 92, or the quality of
     * the source when that source was itself a JPEG. It is now stated explicitly on either side.
     */
    public const QUALITY = 85;

    /**
     * @var array<string, string>
     */
    private const ALIASES = [
        'jpg' => 'jpeg',
    ];

    /**
     * The format comes from the configuration, where it is a free scalar, or from the extension of the source asset.
     * Neither is case normalized, and both spell JPEG either way.
     */
    public static function normalize(string $format): string
    {
        $normalized = mb_strtolower(trim($format));

        return self::ALIASES[$normalized] ?? $normalized;
    }
}
