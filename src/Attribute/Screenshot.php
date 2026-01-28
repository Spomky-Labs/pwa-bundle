<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class Screenshot
{
    /**
     * @param array<string> $sizes Size profiles or explicit dimensions (e.g., ["fhd", "ipad/LP", "1920x1080"])
     * @param array<string, mixed> $parameters Route parameters (e.g., ["id" => 123]). Note: use locales parameter for _locale
     * @param array<string> $locales Locales to generate screenshots for (e.g., ["fr", "en"]). Each locale generates separate screenshots
     * @param null|string $name Base filename for the screenshots (without extension)
     * @param null|string $label Label for the screenshot in the manifest (supports translation)
     * @param null|string $platform Target platform (e.g., "android", "ios", "windows")
     * @param null|string $output Output directory for the screenshots
     * @param null|string $format Output format (e.g., "png", "jpg", "webp")
     */
    public function __construct(
        public array $sizes = [],
        public array $parameters = [],
        public array $locales = [],
        public null|string $name = null,
        public null|string $label = null,
        public null|string $platform = null,
        public null|string $output = null,
        public null|string $format = null,
    ) {
    }
}
